<?php

use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('book trip endpoint works correctly', function () {
    // Create trip statuses
    $scheduledStatus = TripStatus::create([
        'name' => 'scheduled',
        'description' => 'Trip is scheduled',
    ]);

    TripStatus::create([
        'name' => 'in_progress',
        'description' => 'Trip is in progress',
    ]);

    TripStatus::create([
        'name' => 'completed',
        'description' => 'Trip is completed',
    ]);

    TripStatus::create([
        'name' => 'cancelled',
        'description' => 'Trip is cancelled',
    ]);

    // Create vehicle types
    $suvType = VehicleType::create([
        'name' => 'SUV',
        'capacity' => 6,
        'description' => 'Sport Utility Vehicle',
    ]);

    $sedanType = VehicleType::create([
        'name' => 'Sedan',
        'capacity' => 4,
        'description' => 'Standard Sedan',
    ]);

    // Create a user
    $user = User::factory()->create();

    // Create an available driver
    $driverUser = User::factory()->create();
    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'license_number' => 'LIC-12345',
        'is_available' => true,
        'current_latitude' => 40.7128,
        'current_longitude' => -74.0060,
        'location_updated_at' => now(),
    ]);

    // Create vehicles for the driver
    $suvVehicle = Vehicle::create([
        'driver_id' => $driver->id,
        'vehicle_type_id' => $suvType->id,
        'license_plate' => 'SUV-12345',
        'make' => 'Toyota',
        'model' => 'Highlander',
        'year' => 2022,
        'color' => 'Black',
        'is_active' => true,
    ]);

    $sedanVehicle = Vehicle::create([
        'driver_id' => $driver->id,
        'vehicle_type_id' => $sedanType->id,
        'license_plate' => 'SEDAN-12345',
        'make' => 'Toyota',
        'model' => 'Camry',
        'year' => 2022,
        'color' => 'White',
        'is_active' => true,
    ]);

    // Test booking a trip with valid data
    $response = $this->postJson('/api/trips', [
        'user_id' => $user->id,
        'driver_id' => $driver->id,
        'vehicle_type' => 'SUV',
        'origin_address' => '123 Main St, New York, NY',
        'origin_latitude' => 40.7128,
        'origin_longitude' => -74.0060,
        'destination_address' => '456 Park Ave, New York, NY',
        'destination_latitude' => 40.7580,
        'destination_longitude' => -73.9855,
        'scheduled_at' => now()->addHour()->toDateTimeString(),
        'payment_method' => 'credit_card',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'trip' => [
                'id',
                'user_id',
                'driver_id',
                'vehicle_id',
                'status_id',
                'origin_address',
                'origin_latitude',
                'origin_longitude',
                'destination_address',
                'destination_latitude',
                'destination_longitude',
                'scheduled_at',
                'base_fare',
                'total_fare',
                'payment_method',
            ],
        ]);

    // Verify that the trip was created with the correct data
    expect(Trip::where([
        'user_id' => $user->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $suvVehicle->id,
        'status_id' => $scheduledStatus->id,
        'origin_address' => '123 Main St, New York, NY',
        'destination_address' => '456 Park Ave, New York, NY',
        'payment_method' => 'credit_card',
    ])->exists())->toBeTrue();

    // Test booking a trip with invalid vehicle type
    $response = $this->postJson('/api/trips', [
        'user_id' => $user->id,
        'driver_id' => $driver->id,
        'vehicle_type' => 'Truck', // Invalid type
        'origin_address' => '123 Main St, New York, NY',
        'origin_latitude' => 40.7128,
        'origin_longitude' => -74.0060,
        'destination_address' => '456 Park Ave, New York, NY',
        'destination_latitude' => 40.7580,
        'destination_longitude' => -73.9855,
        'scheduled_at' => now()->addHour()->toDateTimeString(),
        'payment_method' => 'credit_card',
    ]);

    $response->assertStatus(422);

    // Test booking a trip with overlapping schedule
    // First, create an existing trip for the driver
    $existingTrip = Trip::create([
        'user_id' => $user->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $suvVehicle->id,
        'status_id' => $scheduledStatus->id,
        'origin_address' => '123 Main St, New York, NY',
        'origin_latitude' => 40.7128,
        'origin_longitude' => -74.0060,
        'destination_address' => '456 Park Ave, New York, NY',
        'destination_latitude' => 40.7580,
        'destination_longitude' => -73.9855,
        'scheduled_at' => now()->addHours(2),
        'base_fare' => 5.00,
        'total_fare' => 5.00,
        'payment_method' => 'credit_card',
        'duration_minutes' => 30,
    ]);

    // Try to book a trip that overlaps with the existing trip
    $response = $this->postJson('/api/trips', [
        'user_id' => $user->id,
        'driver_id' => $driver->id,
        'vehicle_type' => 'SUV',
        'origin_address' => '789 Broadway, New York, NY',
        'origin_latitude' => 40.7200,
        'origin_longitude' => -74.0100,
        'destination_address' => '101 5th Ave, New York, NY',
        'destination_latitude' => 40.7400,
        'destination_longitude' => -73.9900,
        'scheduled_at' => now()->addHours(2)->addMinutes(15)->toDateTimeString(), // Overlaps with existing trip
        'payment_method' => 'cash',
    ]);

    $response->assertStatus(422);
});
