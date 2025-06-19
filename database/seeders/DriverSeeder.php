<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a driver for the admin user
        $adminUser = User::where('email', 'admin@example.com')->first();

        if ($adminUser) {
            Driver::create([
                'user_id' => $adminUser->id,
                'license_number' => 'LIC-ADMIN-001',
                'is_available' => true,
                'current_latitude' => 40.7128, // NYC
                'current_longitude' => -74.0060, // NYC
                'location_updated_at' => now(),
                'rating' => 4.8,
            ]);
        }

        // Create 10 available drivers
        Driver::factory()->count(10)->available()->create();

        // Create 5 unavailable drivers
        Driver::factory()->count(5)->unavailable()->create();
    }
}
