<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationController extends Controller
{
    public function runMigrations()
    {
        try {
            // Check if shift_id is already nullable
            $columns = DB::select("SHOW COLUMNS FROM waiter_table_assignments WHERE Field = 'shift_id'");
            
            if (!empty($columns)) {
                $column = $columns[0];
                $isNullable = ($column->Null === 'YES');
                
                if ($isNullable) {
                    return response()->json([
                        'success' => true,
                        'message' => 'shift_id column is already nullable',
                        'already_done' => true
                    ]);
                }
            }

            // Run pending migrations
            Artisan::call('migrate', ['--force' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Migrations executed successfully',
                'output' => Artisan::output()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
