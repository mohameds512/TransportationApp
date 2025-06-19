<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all drivers
        $drivers = Driver::all();

        // Get vehicle types
        $sedan = VehicleType::where('name', 'Sedan')->first();
        $suv = VehicleType::where('name', 'SUV')->first();
        $minivan = VehicleType::where('name', 'Minivan')->first();
        $luxury = VehicleType::where('name', 'Luxury')->first();
        $compact = VehicleType::where('name', 'Compact')->first();
        $electric = VehicleType::where('name', 'Electric')->first();

        // Create vehicles for each driver
        foreach ($drivers as $driver) {
            // Each driver has 1-3 vehicles
            $numVehicles = rand(1, 3);

            for ($i = 0; $i < $numVehicles; $i++) {
                // Randomly select a vehicle type
                $vehicleTypes = [$sedan, $suv, $minivan, $luxury, $compact, $electric];
                $vehicleType = $vehicleTypes[array_rand($vehicleTypes)];

                // Create the vehicle
                Vehicle::factory()
                    ->create([
                        'driver_id' => $driver->id,
                        'vehicle_type_id' => $vehicleType->id,
                        'current_latitude' => $driver->current_latitude,
                        'current_longitude' => $driver->current_longitude,
                        'location_updated_at' => $driver->location_updated_at,
                    ]);
            }
        }

        // Create some additional vehicles of specific types
        // 5 SUVs
        Vehicle::factory()->count(5)->ofType('SUV')->create();

        // 5 Sedans
        Vehicle::factory()->count(5)->ofType('Sedan')->create();

        // 3 Luxury vehicles
        Vehicle::factory()->count(3)->ofType('Luxury')->create();
    }
}
