<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;
use Illuminate\Support\Str;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        // Check if hotel already exists
        if (Hotel::count() === 0) {
            Hotel::create([
                'id' => Str::uuid(),
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
            ]);

            $this->command->info('Default hotel created successfully!');
        } else {
            $this->command->info('Hotel already exists, skipping seeder.');
        }
    }
}
