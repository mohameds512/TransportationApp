<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users, drivers, and vehicles
        $users = User::all();
        $drivers = Driver::with('vehicles')->get();

        // Get trip statuses
        $scheduledStatus = TripStatus::where('name', 'scheduled')->first();
        $inProgressStatus = TripStatus::where('name', 'in_progress')->first();
        $completedStatus = TripStatus::where('name', 'completed')->first();
        $cancelledStatus = TripStatus::where('name', 'cancelled')->first();

        // Get all vehicles
        $vehicles = Vehicle::all();

        // Create scheduled trips
        for ($i = 0; $i < 10; $i++) {
            $user = $users->random();
            $vehicle = $vehicles->random();
            $driver = Driver::find($vehicle->driver_id);

            Trip::factory()
                ->scheduled()
                ->create([
                    'user_id' => $user->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'status_id' => $scheduledStatus->id,
                ]);
        }

        // Create in-progress trips
        for ($i = 0; $i < 5; $i++) {
            $user = $users->random();
            $vehicle = $vehicles->random();
            $driver = Driver::find($vehicle->driver_id);

            Trip::factory()
                ->inProgress()
                ->create([
                    'user_id' => $user->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'status_id' => $inProgressStatus->id,
                ]);
        }

        // Create completed trips
        for ($i = 0; $i < 20; $i++) {
            $user = $users->random();
            $vehicle = $vehicles->random();
            $driver = Driver::find($vehicle->driver_id);

            Trip::factory()
                ->completed()
                ->create([
                    'user_id' => $user->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'status_id' => $completedStatus->id,
                ]);
        }

        // Create cancelled trips
        for ($i = 0; $i < 5; $i++) {
            $user = $users->random();
            $vehicle = $vehicles->random();
            $driver = Driver::find($vehicle->driver_id);

            Trip::factory()
                ->cancelled()
                ->create([
                    'user_id' => $user->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'status_id' => $cancelledStatus->id,
                ]);
        }

        // Create trips for specific users and drivers
        foreach ($users->take(5) as $user) {
            // Each user has 2-5 trips
            $numTrips = rand(2, 5);

            for ($i = 0; $i < $numTrips; $i++) {
                // Randomly select a driver with vehicles
                $driver = $drivers->filter(function ($driver) {
                    return $driver->vehicles->count() > 0;
                })->random();

                // Randomly select one of the driver's vehicles
                $vehicle = $driver->vehicles->random();

                // Randomly select a trip status
                $statuses = [$scheduledStatus, $inProgressStatus, $completedStatus, $cancelledStatus];
                $status = $statuses[array_rand($statuses)];

                // Create the trip
                Trip::factory()
                    ->create([
                        'user_id' => $user->id,
                        'driver_id' => $driver->id,
                        'vehicle_id' => $vehicle->id,
                        'status_id' => $status->id,
                    ]);
            }
        }
    }
}
