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

        // 12. Check roles & permissions tables (RBAC)
        try {
            $results['db_has_roles'] = Schema::hasTable('roles');
            $results['db_has_permissions'] = Schema::hasTable('permissions');
            $results['db_has_role_permissions'] = Schema::hasTable('role_permissions');
            $results['db_has_user_roles'] = Schema::hasTable('user_roles');
        } catch (\Throwable $e) {
            $results['rbac_tables_error'] = $e->getMessage();
        }

        // 13. Try the EXACT user query that UserController runs (with permission middleware context)
        try {
            $query = \App\Models\User::select([
                'id','first_name','last_name','email','phone','role','is_active','last_login','created_at','updated_at'
            ])->paginate(5);
            $results['users_paginate_ok'] = true;
            $results['users_count'] = $query->total();
        } catch (\Throwable $e) {
            $results['users_paginate_ok'] = false;
            $results['users_paginate_error'] = $e->getMessage();
        }

        // 14. Try roles query
        try {
            \App\Models\Role::where('is_active', true)->limit(1)->get();
            $results['roles_query_ok'] = true;
        } catch (\Throwable $e) {
            $results['roles_query_ok'] = false;
            $results['roles_error'] = $e->getMessage();
        }

        // 15. Try permissions query
        try {
            \App\Models\Permission::where('is_active', true)->limit(1)->get();
            $results['permissions_query_ok'] = true;
        } catch (\Throwable $e) {
            $results['permissions_query_ok'] = false;
            $results['permissions_error'] = $e->getMessage();
        }

        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Test email sending — helps diagnose SMTP config issues.
     * Usage: GET /api/deploy/test-email?to=youremail@gmail.com&secret=YOUR_DEPLOY_SECRET
     */
    public function testEmail(Request $request): JsonResponse
    {
        $secret = env('DEPLOY_SECRET', '');
        if (empty($secret) || $request->query('secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $to = $request->query('to', env('MAIL_FROM_ADDRESS'));

        $result = [
            'mail_host'       => env('MAIL_HOST'),
            'mail_port'       => env('MAIL_PORT'),
            'mail_scheme'     => env('MAIL_SCHEME'),
            'mail_username'   => env('MAIL_USERNAME'),
            'mail_from'       => env('MAIL_FROM_ADDRESS'),
            'sending_to'      => $to,
        ];

        // Test 1: Raw text email
        try {
            \Illuminate\Support\Facades\Mail::raw(
                'Test email from HMS at ' . now() . '. If you see this, SMTP is working correctly!',
                function ($message) use ($to) {
                    $message->to($to)->subject('HMS SMTP Test — ' . now()->format('H:i:s'));
                }
            );
            $result['test1_raw_email'] = 'SUCCESS';
        } catch (\Throwable $e) {
            $result['test1_raw_email'] = 'FAILED: ' . $e->getMessage();
        }

        // Test 2: Full NewUserCreated mailable (the actual welcome email template)
        try {
            $fakeUser = new \App\Models\User([
                'first_name' => 'Test',
                'last_name'  => 'User',
                'email'      => $to,
                'role'       => 'staff',
            ]);
            \Illuminate\Support\Facades\Mail::to($to)->send(
                new \App\Mail\NewUserCreated($fakeUser, 'TestPass123!')
            );
            $result['test2_new_user_mailable'] = 'SUCCESS - welcome email template works!';
        } catch (\Throwable $e) {
            $result['test2_new_user_mailable'] = 'FAILED: ' . $e->getMessage();
            $result['test2_error_class']       = get_class($e);
            $result['test2_error_file']        = $e->getFile() . ':' . $e->getLine();
        }

        return response()->json($result, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Platform statistics for Admin dashboard.
     * Usage: GET /api/platform/statistics (requires auth:sanctum)
     */
    public function platformStatistics(): JsonResponse
    {
        $data = [];

        try { $data['total_hotels']       = \App\Models\Hotel::count(); } catch (\Throwable $e) { $data['total_hotels'] = 0; }
        try { $data['active_hotels']      = \App\Models\Hotel::where('is_active', true)->count(); } catch (\Throwable $e) { $data['active_hotels'] = $data['total_hotels'] ?? 0; }
        try { $data['total_users']        = \App\Models\User::count(); } catch (\Throwable $e) { $data['total_users'] = 0; }
        try { $data['total_rooms']        = \App\Models\Room::count(); } catch (\Throwable $e) { $data['total_rooms'] = 0; }
        try { $data['total_reservations'] = \App\Models\Reservation::count(); } catch (\Throwable $e) { $data['total_reservations'] = 0; }

        return response()->json([
            'success' => true,
            'data'    => $data,
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
