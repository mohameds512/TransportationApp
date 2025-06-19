<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create predefined vehicle types
        $vehicleTypes = [
            [
                'name' => 'Sedan',
                'capacity' => 4,
                'description' => 'Standard sedan with room for 4 passengers',
            ],
            [
                'name' => 'SUV',
                'capacity' => 6,
                'description' => 'Sport Utility Vehicle with room for 6 passengers',
            ],
            [
                'name' => 'Minivan',
                'capacity' => 7,
                'description' => 'Minivan with room for 7 passengers',
            ],
            [
                'name' => 'Luxury',
                'capacity' => 4,
                'description' => 'Luxury vehicle with premium features',
            ],
            [
                'name' => 'Compact',
                'capacity' => 4,
                'description' => 'Compact car with room for 4 passengers',
            ],
            [
                'name' => 'Electric',
                'capacity' => 4,
                'description' => 'Electric vehicle with zero emissions',
            ],
        ];

        foreach ($vehicleTypes as $vehicleType) {
            VehicleType::create($vehicleType);
        }
    }
}
