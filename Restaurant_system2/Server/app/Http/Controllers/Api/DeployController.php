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
     * Protected endpoint to run migrations and other deploy tasks.
     * Requires DEPLOY_SECRET env variable to match the ?secret= query param.
     * 
     * Usage: GET /api/deploy/migrate?secret=YOUR_DEPLOY_SECRET
     */
    public function migrate(Request $request): JsonResponse
    {
        // Verify secret
        $secret = env('DEPLOY_SECRET', '');
        if (empty($secret) || $request->query('secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $output = [];
        $errors = [];

        // 1. Clear caches
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            $output[] = '✅ Caches cleared';
        } catch (\Throwable $e) {
            $errors[] = 'Cache clear failed: ' . $e->getMessage();
        }

        // 2. Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = Artisan::output();
            $output[] = '✅ Migrations ran';
            $output[] = $migrationOutput;
        } catch (\Throwable $e) {
            $errors[] = 'Migration failed: ' . $e->getMessage();
            Log::error('[Deploy] Migration failed: ' . $e->getMessage());
        }

        // 3. Re-cache
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            $output[] = '✅ Config/route/view cached';
        } catch (\Throwable $e) {
            $errors[] = 'Cache rebuild failed: ' . $e->getMessage();
        }

        // 4. DB connectivity check
        try {
            $tables = DB::select('SHOW TABLES');
            $output[] = '✅ DB connected — ' . count($tables) . ' tables found';
        } catch (\Throwable $e) {
            $errors[] = 'DB check failed: ' . $e->getMessage();
        }

        return response()->json([
            'status'  => empty($errors) ? 'success' : 'partial',
            'output'  => $output,
            'errors'  => $errors,
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
