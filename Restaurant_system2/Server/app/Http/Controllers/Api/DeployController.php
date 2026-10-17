<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeployController extends \App\Http\Controllers\Controller
{
    /**
     * Public diagnostic - no secret needed, safe to expose (read-only).
     * Usage: GET /api/deploy/diagnose
     */
    public function diagnose(Request $request): JsonResponse
    {
        $results = [];

        // 1. DB connection
        try {
            $tables = DB::select('SHOW TABLES');
            $tableNames = array_map(fn($t) => array_values((array)$t)[0], $tables);
            $results['db_connected'] = true;
            $results['db_tables_count'] = count($tables);
            $results['db_has_reservations'] = in_array('reservations', $tableNames);
            $results['db_has_check_ins'] = in_array('check_ins', $tableNames);
            $results['db_has_guests'] = in_array('guests', $tableNames);
            $results['db_has_rooms'] = in_array('rooms', $tableNames);
            $results['db_has_hotels'] = in_array('hotels', $tableNames);
        } catch (\Throwable $e) {
            $results['db_connected'] = false;
            $results['db_error'] = $e->getMessage();
            return response()->json($results);
        }

        // 2. Try reservation query
        try {
            $count = DB::table('reservations')->count();
            $results['reservations_count'] = $count;
            $results['reservations_query_ok'] = true;
        } catch (\Throwable $e) {
            $results['reservations_query_ok'] = false;
            $results['reservations_error'] = $e->getMessage();
        }

        // 3. Try reservation with relations
        try {
            \App\Models\Reservation::with(['guest', 'room', 'checkIn'])->limit(1)->get();
            $results['reservation_with_relations_ok'] = true;
        } catch (\Throwable $e) {
            $results['reservation_with_relations_ok'] = false;
            $results['reservation_with_relations_error'] = $e->getMessage();
        }

        // 4. Check columns in reservations table
        try {
            $cols = DB::select('SHOW COLUMNS FROM reservations');
            $results['reservation_columns'] = array_map(fn($c) => $c->Field, $cols);
        } catch (\Throwable $e) {
            $results['reservation_columns_error'] = $e->getMessage();
        }

        // 5. Check columns in check_ins table
        try {
            $cols = DB::select('SHOW COLUMNS FROM check_ins');
            $results['check_in_columns'] = array_map(fn($c) => $c->Field, $cols);
        } catch (\Throwable $e) {
            $results['check_in_columns_error'] = $e->getMessage();
        }

        // 6. Hotel model check (for platform/statistics)
        try {
            $hotel = \App\Models\Hotel::first();
            $results['hotel_found'] = !!$hotel;
            $results['hotel_id'] = $hotel?->id;
        } catch (\Throwable $e) {
            $results['hotel_error'] = $e->getMessage();
        }

        // 7. Check users table columns
        try {
            $cols = DB::select('SHOW COLUMNS FROM users');
            $results['user_columns'] = array_map(fn($c) => $c->Field, $cols);
        } catch (\Throwable $e) {
            $results['user_columns_error'] = $e->getMessage();
        }

        // 8. Try users query
        try {
            \App\Models\User::select(['id','first_name','last_name','email','role','is_active','last_login','created_at'])->limit(1)->get();
            $results['users_query_ok'] = true;
        } catch (\Throwable $e) {
            $results['users_query_ok'] = false;
            $results['users_error'] = $e->getMessage();
        }

        // 9. Check orders table columns
        try {
            $cols = DB::select('SHOW COLUMNS FROM orders');
            $results['order_columns'] = array_map(fn($c) => $c->Field, $cols);
        } catch (\Throwable $e) {
            $results['order_columns_error'] = $e->getMessage();
        }

        // 10. Try orders query
        try {
            DB::table('orders')->limit(1)->get();
            $results['orders_query_ok'] = true;
        } catch (\Throwable $e) {
            $results['orders_query_ok'] = false;
            $results['orders_error'] = $e->getMessage();
        }

        // 11. Try Hotel::where('is_active') for platform/statistics
        try {
            \App\Models\Hotel::where('is_active', true)->count();
            $results['platform_stats_query_ok'] = true;
        } catch (\Throwable $e) {
            $results['platform_stats_query_ok'] = false;
            $results['platform_stats_error'] = $e->getMessage();
        }


        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Platform statistics for Admin dashboard.
     * Usage: GET /api/platform/statistics (requires auth:sanctum)
     */
    public function platformStatistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_hotels'       => \App\Models\Hotel::count(),
                'active_hotels'      => \App\Models\Hotel::where('is_active', true)->count(),
                'total_users'        => \App\Models\User::count(),
                'total_rooms'        => \App\Models\Room::count(),
                'total_reservations' => \App\Models\Reservation::count(),
            ],
        ]);
    }

    /**
     * Protected endpoint to run migrations and other deploy tasks.
     * Requires DEPLOY_SECRET env variable to match the ?secret= query param.
     * Usage: GET /api/deploy/migrate?secret=YOUR_DEPLOY_SECRET
     */
    public function migrate(Request $request): JsonResponse
    {
        $secret = env('DEPLOY_SECRET', '');
        if (empty($secret) || $request->query('secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $output = [];
        $errors = [];

        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            $output[] = '✅ Caches cleared';
        } catch (\Throwable $e) {
            $errors[] = 'Cache clear failed: ' . $e->getMessage();
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output[] = '✅ Migrations ran';
            $output[] = Artisan::output();
        } catch (\Throwable $e) {
            $errors[] = 'Migration failed: ' . $e->getMessage();
            Log::error('[Deploy] Migration failed: ' . $e->getMessage());
        }

        try {
            Artisan::call('config:cache');
            Artisan::call('view:cache');
            // NOTE: route:cache intentionally skipped — closure routes cannot be serialized
            $output[] = '✅ Config/view cached';
        } catch (\Throwable $e) {
            $errors[] = 'Cache rebuild failed: ' . $e->getMessage();
        }

        try {
            $tables = DB::select('SHOW TABLES');
            $output[] = '✅ DB connected — ' . count($tables) . ' tables found';
        } catch (\Throwable $e) {
            $errors[] = 'DB check failed: ' . $e->getMessage();
        }

        return response()->json([
            'status' => empty($errors) ? 'success' : 'partial',
            'output' => $output,
            'errors' => $errors,
        ]);
    }

    /**
     * Check DB status and migration status without running them.
     * Usage: GET /api/deploy/status?secret=YOUR_DEPLOY_SECRET
     */
    public function status(Request $request): JsonResponse
    {
        $secret = env('DEPLOY_SECRET', '');
        if (empty($secret) || $request->query('secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $output = [];
        $errors = [];

        try {
            $tables = DB::select('SHOW TABLES');
            $output['db_tables'] = count($tables);
            $output['db_connected'] = true;
        } catch (\Throwable $e) {
            $errors[] = 'DB: ' . $e->getMessage();
            $output['db_connected'] = false;
        }

        try {
            Artisan::call('migrate:status', ['--no-ansi' => true]);
            $output['migration_status'] = Artisan::output();
        } catch (\Throwable $e) {
            $errors[] = 'migrate:status failed: ' . $e->getMessage();
        }

        return response()->json([
            'status' => empty($errors) ? 'ok' : 'error',
            'data'   => $output,
            'errors' => $errors,
        ]);
    }
}
