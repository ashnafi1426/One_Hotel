<?php

namespace App\Console\Commands;

use App\Models\RestaurantTable;
use App\Models\Room;
use App\Services\QRCodeService;
use Illuminate\Console\Command;

class RegenerateAllQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:regenerate-all {--url= : Optional custom base URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate all room and restaurant table QR codes with the deployed frontend URL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $baseUrl = $this->option('url') ?: rtrim(config('app.frontend_url', env('APP_FRONTEND_URL', 'https://one-hotel-lemon.vercel.app')), '/');

        $this->info("Starting QR code regeneration with base URL: {$baseUrl}");

        // Regenerate Rooms
        $rooms = Room::all();
        $this->info("Found {$rooms->count()} rooms to update...");

        $roomSuccess = 0;
        foreach ($rooms as $room) {
            try {
                if (!$room->qr_token) {
                    $room->qr_token = Room::generateUniqueToken();
                }

                $newPath = QRCodeService::regenerateQRCode($room, $baseUrl);
                $this->line("✓ Room {$room->room_number} (token: {$room->qr_token}) -> {$newPath}");
                $roomSuccess++;
            } catch (\Throwable $e) {
                $this->error("✗ Failed to regenerate QR for Room {$room->room_number}: {$e->getMessage()}");
            }
        }

        // Regenerate Restaurant Tables
        $tables = RestaurantTable::all();
        $this->info("Found {$tables->count()} restaurant tables to update...");

        $tableSuccess = 0;
        foreach ($tables as $table) {
            try {
                if (!$table->qr_token) {
                    $table->qr_token = RestaurantTable::generateUniqueToken();
                }

                $newPath = $table->regenerateQRCode($baseUrl);
                $this->line("✓ Table {$table->table_number} (token: {$table->qr_token}) -> {$newPath}");
                $tableSuccess++;
            } catch (\Throwable $e) {
                $this->error("✗ Failed to regenerate QR for Table {$table->table_number}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Successfully regenerated {$roomSuccess} room QR codes and {$tableSuccess} table QR codes.");
        $this->info("All scanned QR codes will now direct to: {$baseUrl}/order/{TOKEN}");

        return Command::SUCCESS;
    }
}
