<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call seeders in the correct order
        $this->call([
            // First, seed the reference tables
            VehicleTypeSeeder::class,
            TripStatusSeeder::class,

            // Then, seed the user-related tables
            UserSeeder::class,
            DriverSeeder::class,

            // Next, seed the vehicle table
            VehicleSeeder::class,

            // Finally, seed the trips table
            TripSeeder::class,
        ]);
    }
}
