<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\WaiterTableAssignment;
use App\Models\Waiter;
use App\Models\RestaurantTable;
use App\Models\HotelShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TableAssignmentTestController extends Controller
{
    public function testAssignment(Request $request)
    {
        try {
            $waiter = Waiter::where('status', 'active')->first();
            if (!$waiter) {
                return response()->json([
                    'error' => 'No active waiter found',
                    'waiters_count' => Waiter::count()
                ], 400);
            }

            $table = RestaurantTable::first();
            if (!$table) {
                return response()->json([
                    'error' => 'No table found',
                    'tables_count' => RestaurantTable::count()
                ], 400);
            }

            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'Not authenticated'
                ], 401);
            }

            $columns = Schema::getColumnListing('waiter_table_assignments');

            $assignment = WaiterTableAssignment::create([
                'waiter_id' => $waiter->id,
                'table_id' => $table->id,
                'shift_id' => HotelShift::where('is_active', true)->value('id'),
                'assignment_date' => today()->toDateString(),
                'priority' => 'primary',
                'status' => 'active',
                'assigned_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'assignment' => $assignment,
                'debug' => [
                    'waiter_id' => $waiter->id,
                    'waiter_id_type' => gettype($waiter->id),
                    'table_id' => $table->id,
                    'table_id_type' => gettype($table->id),
                    'user_id' => $user->id,
                    'user_id_type' => gettype($user->id),
                    'table_columns' => $columns
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ], 500);
        }
    }
}