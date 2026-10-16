<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanSingleHotelData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:singlehotel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all operational and transactional tables while preserving core hotel setup (hotel record, users, roles, permissions, rooms, floors, tables, migrations, and auth tokens).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database cleanup...');

        // Tables we want to keep intact.
        $preserve = [
            'hotels',
            'users',
            'roles',
            'permissions',
            'room_types',
            'rooms',
            'floors',
            'restaurant_tables', // typical name for tables in the dining area
            'migrations',
            'password_resets',
            'personal_access_tokens',
        ];

        // Disable foreign key checks for MySQL.
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // Get all tables in the current database.
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(function ($item) {
                // The key varies depending on the DB name, get first property.
                $array = (array) $item;
                return reset($array);
            })
            ->filter(function ($table) use ($preserve) {
                return !in_array($table, $preserve);
            })
            ->values();

        if ($tables->isEmpty()) {
            $this->info('No tables to truncate.');
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            return 0;
        }

        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
                $this->line("<info>Truncated</info> {$table}");
            } catch (\Exception $e) {
                $this->error("Failed to truncate {$table}: {$e->getMessage()}");
            }
        }

        // Re‑enable foreign key checks.
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        $this->info('Database cleanup completed.');
        return 0;
    }
}
?>
