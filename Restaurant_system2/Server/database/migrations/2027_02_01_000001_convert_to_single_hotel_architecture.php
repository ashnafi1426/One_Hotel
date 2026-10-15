<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * All tables containing tenant hotel_id to be stripped.
     */
    protected array $tablesToStripHotelId = [
        'audit_logs',
        'cancellation_policies',
        'categories',
        'check_ins',
        'check_outs',
        'complaint_tickets',
        'contact_messages',
        'delivery_logs',
        'delivery_tasks',
        'floors',
        'guests',
        'hotel_settings',
        'hotel_shifts',
        'housekeeping_tasks',
        'inventory_management',
        'invoices',
        'laundry_requests',
        'manager_activity_logs',
        'manager_announcements',
        'manager_audit_logs',
        'manager_dashboard_settings',
        'manager_notifications',
        'manager_reports',
        'menu_item_reviews',
        'menu_items',
        'notifications',
        'orders',
        'payment_transaction_logs',
        'payments',
        'performance_metrics',
        'reservation_audit_logs',
        'reservations',
        'restaurant_charges',
        'restaurant_sections',
        'restaurant_tables',
        'review_notifications',
        'roles',
        'room_service_deliveries',
        'room_types',
        'rooms',
        'tax_rates',
        'user_permissions',
        'user_roles',
        'waiter_assignments',
        'waiter_floor_assignments',
        'waiter_notifications',
        'waiter_performance',
        'waiter_table_assignments',
        'waiters',
    ];

    public function up(): void
    {
        // 1. Ensure authoritative singleton hotel record exists in hotels table
        if (Schema::hasTable('hotels')) {
            $sheratenId = '41b094f8-7ed2-4af0-bd16-6f0533e34544';
            $exists = DB::table('hotels')->where('id', $sheratenId)->exists();
            if (!$exists) {
                // Delete any test/multi hotels and insert authoritative Sheraten Hotel
                DB::table('hotels')->delete();
                DB::table('hotels')->insert([
                    'id' => $sheratenId,
                    'name' => 'Sheraten Hotel',
                    'slug' => 'sheraten',
                    'email' => 'skylite@gmail.com',
                    'phone' => '+251968277909',
                    'address' => 'Bole Sub-City',
                    'city' => 'Addis Ababa',
                    'country' => 'Ethiopia',
                    'timezone' => 'Africa/Addis_Ababa',
                    'currency' => 'ETB',
                    'status' => 'active',
                    'front_desk_hours' => '24/7',
                    'dining_hours' => '06:00 - 23:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Drop obsolete multi-tenant tables
        Schema::dropIfExists('hotel_users');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('hotel_floors');

        // 3. Drop is_platform_admin from users table
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_platform_admin')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('is_platform_admin');
                });
            } catch (\Throwable $e) {}
        }

        // 4. Drop foreign keys and indexes from all business tables
        foreach ($this->tablesToStripHotelId as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            // Drop foreign key if present
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = schema()
                  AND TABLE_NAME = '{$tableName}'
                  AND (REFERENCED_TABLE_NAME = 'hotels' OR COLUMN_NAME = 'hotel_id')
            ");

            foreach ($foreignKeys as $fk) {
                try {
                    DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Throwable $e) {}
            }

            // Drop indexes containing hotel_id
            $indexes = DB::select("
                SELECT DISTINCT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = schema()
                  AND TABLE_NAME = '{$tableName}'
                  AND COLUMN_NAME = 'hotel_id'
                  AND INDEX_NAME != 'PRIMARY'
            ");

            foreach ($indexes as $idx) {
                try {
                    DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `{$idx->INDEX_NAME}`");
                } catch (\Throwable $e) {}
            }

            // Drop composite unique indexes that had hotel_id
            $compositeIndexes = [
                'categories_hotel_active_order_idx',
                'delivery_tasks_hotel_status_created_idx',
                'floors_hotel_id_floor_number_index',
                'floors_hotel_id_is_active_index',
                'menu_items_hotel_avail_cat_idx',
                'menu_items_hotel_id_is_available_idx',
                'orders_hotel_created_idx',
                'orders_hotel_status_updated_idx',
                'payment_transaction_logs_hotel_id_created_at_index',
                'payments_hotel_status_created_idx',
                'reservations_hotel_created_idx',
                'reservations_hotel_status_idx',
                'restaurant_sections_hotel_id_is_active_index',
                'roles_hotel_id_is_active_index',
                'roles_hotel_id_name_index',
                'rooms_hotel_status_idx',
                'user_roles_hotel_id_role_id_index',
                'cancellation_policies_hotel_id_is_active_index',
            ];

            foreach ($compositeIndexes as $compIdx) {
                try {
                    DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `{$compIdx}`");
                } catch (\Throwable $e) {}
            }

            // Drop hotel_id column
            if (Schema::hasColumn($tableName, 'hotel_id')) {
                try {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropColumn('hotel_id');
                    });
                } catch (\Throwable $e) {}
            }
        }

        // 5. Restore natural single-hotel unique constraints
        if (Schema::hasTable('rooms')) {
            try {
                Schema::table('rooms', function (Blueprint $table) {
                    $table->unique('room_number');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('room_types')) {
            try {
                Schema::table('room_types', function (Blueprint $table) {
                    $table->unique('name');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('categories')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique('slug');
                    $table->unique('name');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->unique('order_number');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('restaurant_tables')) {
            try {
                Schema::table('restaurant_tables', function (Blueprint $table) {
                    $table->unique('table_number');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('hotel_shifts')) {
            try {
                Schema::table('hotel_shifts', function (Blueprint $table) {
                    $table->unique('name');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('roles')) {
            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->unique('slug');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('user_roles')) {
            try {
                Schema::table('user_roles', function (Blueprint $table) {
                    $table->unique(['user_id', 'role_id']);
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('user_permissions')) {
            try {
                Schema::table('user_permissions', function (Blueprint $table) {
                    $table->unique(['user_id', 'permission_id']);
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        // Schema reversal
    }
};
