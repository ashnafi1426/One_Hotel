<?php

/**
 * Hotel Setup Script
 * Run this once to create the hotels table and seed initial data
 * Usage: php setup-hotel.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "=== Hotel Setup Script ===\n\n";

try {
    // Check if hotels table exists
    if (!Schema::hasTable('hotels')) {
        echo "❌ Hotels table does not exist. Please run migrations first:\n";
        echo "   php artisan migrate\n\n";
        exit(1);
    }

    echo "✅ Hotels table exists\n";

    // Check if hotel record exists
    $hotelCount = DB::table('hotels')->count();
    echo "Current hotel records: $hotelCount\n\n";

    if ($hotelCount === 0) {
        echo "Creating default hotel record...\n";
        
        DB::table('hotels')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Executive Hotel',
            'slug' => 'executive-hotel',
            'email' => 'info@executivehotel.com',
            'reservation_email' => 'reservations@executivehotel.com',
            'phone' => '+251 11 123 4567',
            'reservation_phone' => '+251 11 123 4568',
            'address' => 'Bole Road, Near Edna Mall',
            'city' => 'Addis Ababa',
            'country' => 'Ethiopia',
            'front_desk_hours' => 'Open 24/7 / 365 Days',
            'dining_hours' => '06:00 AM – 11:00 PM Daily',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Default hotel created successfully!\n\n";
    } else {
        echo "ℹ️  Hotel record already exists, skipping creation.\n\n";
    }

    // Display current hotel info
    $hotel = DB::table('hotels')->first();
    if ($hotel) {
        echo "Current Hotel Information:\n";
        echo "  Name: {$hotel->name}\n";
        echo "  Email: {$hotel->email}\n";
        echo "  Phone: {$hotel->phone}\n";
        echo "  City: {$hotel->city}\n";
        echo "  Status: {$hotel->status}\n\n";
    }

    echo "✅ Setup complete!\n";
    echo "\nYou can now:\n";
    echo "1. Go to Admin Profile → Hotel Settings tab\n";
    echo "2. Update the hotel information\n";
    echo "3. Save changes\n\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
