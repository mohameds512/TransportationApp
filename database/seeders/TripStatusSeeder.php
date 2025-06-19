<?php

namespace Database\Seeders;

use App\Models\TripStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TripStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create predefined trip statuses
        $tripStatuses = [
            [
                'name' => 'scheduled',
                'description' => 'Trip is scheduled for the future',
            ],
            [
                'name' => 'in_progress',
                'description' => 'Trip is currently in progress',
            ],
            [
                'name' => 'completed',
                'description' => 'Trip has been completed successfully',
            ],
            [
                'name' => 'cancelled',
                'description' => 'Trip has been cancelled',
            ],
        ];

        foreach ($tripStatuses as $tripStatus) {
            TripStatus::create($tripStatus);
        }
    }
}
