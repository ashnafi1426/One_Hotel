<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('waiter_floor_assignments')) {
            Schema::table('waiter_floor_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('waiter_floor_assignments', 'hotel_id')) {
                    $table->dropColumn('hotel_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('waiter_floor_assignments')) {
            Schema::table('waiter_floor_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('waiter_floor_assignments', 'hotel_id')) {
                    $table->uuid('hotel_id')->nullable()->after('id');
                }
            });
        }
    }
};
