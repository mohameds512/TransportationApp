<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\TripStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // NYC area coordinates
        $originLat = fake()->latitude(40.5, 41.0);
        $originLng = fake()->longitude(-74.5, -73.5);
        $destLat = fake()->latitude(40.5, 41.0);
        $destLng = fake()->longitude(-74.5, -73.5);

        // Calculate base fare and total fare
        $baseFare = 5.00;
        $distanceFare = fake()->randomFloat(2, 0, 20);
        $timeFare = fake()->randomFloat(2, 0, 15);
        $totalFare = $baseFare + $distanceFare + $timeFare;

        // Generate scheduled time (between now and 7 days in the future)
        $scheduledAt = fake()->dateTimeBetween('now', '+7 days');

        // Determine if the trip has started or completed based on scheduled time
        $startedAt = null;
        $completedAt = null;
        $distanceKm = null;
        $durationMinutes = null;

        // If scheduled time is in the past, the trip might have started or completed
        if ($scheduledAt < now()) {
            // 70% chance the trip has started
            if (fake()->boolean(70)) {
                $startedAt = clone $scheduledAt;
                $durationMinutes = fake()->numberBetween(10, 60);
                $distanceKm = fake()->randomFloat(2, 1, 20);

                // 60% chance the trip has completed
                if (fake()->boolean(60)) {
                    $completedAt = (clone $startedAt)->modify("+{$durationMinutes} minutes");
                }
            }
        }

        return [
            'user_id' => User::factory(),
            'driver_id' => Driver::factory(),
            'vehicle_id' => Vehicle::factory(),
            'status_id' => TripStatus::factory(),
            'origin_address' => fake()->streetAddress() . ', ' . fake()->city() . ', NY',
            'origin_latitude' => $originLat,
            'origin_longitude' => $originLng,
            'destination_address' => fake()->streetAddress() . ', ' . fake()->city() . ', NY',
            'destination_latitude' => $destLat,
            'destination_longitude' => $destLng,
            'scheduled_at' => $scheduledAt,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'base_fare' => $baseFare,
            'distance_fare' => $distanceFare,
            'time_fare' => $timeFare,
            'total_fare' => $totalFare,
            'distance_km' => $distanceKm,
            'duration_minutes' => $durationMinutes,
            'payment_method' => fake()->randomElement(['cash', 'credit_card', 'paypal']),
            'is_paid' => $completedAt ? fake()->boolean(80) : false, // 80% chance of being paid if completed
            'user_rating' => $completedAt ? fake()->optional(0.7)->randomFloat(1, 3.0, 5.0) : null, // 70% chance of having a rating if completed
            'driver_rating' => $completedAt ? fake()->optional(0.6)->randomFloat(1, 3.0, 5.0) : null, // 60% chance of having a rating if completed
            'notes' => fake()->optional(0.3)->sentence(), // 30% chance of having notes
        ];
    }

    /**
     * Indicate that the trip is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            $scheduledStatus = TripStatus::where('name', 'scheduled')->first();

            if (!$scheduledStatus) {
                $scheduledStatus = TripStatus::factory()->scheduled()->create();
            }

            return [
                'status_id' => $scheduledStatus->id,
                'scheduled_at' => fake()->dateTimeBetween('now', '+7 days'),
                'started_at' => null,
                'completed_at' => null,
                'is_paid' => false,
                'user_rating' => null,
                'driver_rating' => null,
            ];
        });
    }

    /**
     * Indicate that the trip is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            $inProgressStatus = TripStatus::where('name', 'in_progress')->first();

            if (!$inProgressStatus) {
                $inProgressStatus = TripStatus::factory()->inProgress()->create();
            }

            $scheduledAt = fake()->dateTimeBetween('-2 days', '-1 hour');

            return [
                'status_id' => $inProgressStatus->id,
                'scheduled_at' => $scheduledAt,
                'started_at' => $scheduledAt,
                'completed_at' => null,
                'is_paid' => false,
                'user_rating' => null,
                'driver_rating' => null,
            ];
        });
    }

    /**
     * Indicate that the trip is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $completedStatus = TripStatus::where('name', 'completed')->first();

            if (!$completedStatus) {
                $completedStatus = TripStatus::factory()->completed()->create();
            }

            $scheduledAt = fake()->dateTimeBetween('-7 days', '-1 day');
            $durationMinutes = fake()->numberBetween(10, 60);
            $startedAt = clone $scheduledAt;
            $completedAt = (clone $startedAt)->modify("+{$durationMinutes} minutes");
            $distanceKm = fake()->randomFloat(2, 1, 20);

            return [
                'status_id' => $completedStatus->id,
                'scheduled_at' => $scheduledAt,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'distance_km' => $distanceKm,
                'duration_minutes' => $durationMinutes,
                'is_paid' => fake()->boolean(80), // 80% chance of being paid
                'user_rating' => fake()->optional(0.7)->randomFloat(1, 3.0, 5.0), // 70% chance of having a rating
                'driver_rating' => fake()->optional(0.6)->randomFloat(1, 3.0, 5.0), // 60% chance of having a rating
            ];
        });
    }

    /**
     * Indicate that the trip is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            $cancelledStatus = TripStatus::where('name', 'cancelled')->first();

            if (!$cancelledStatus) {
                $cancelledStatus = TripStatus::factory()->cancelled()->create();
            }

            return [
                'status_id' => $cancelledStatus->id,
                'scheduled_at' => fake()->dateTimeBetween('-7 days', '+7 days'),
                'started_at' => null,
                'completed_at' => null,
                'is_paid' => false,
                'user_rating' => null,
                'driver_rating' => null,
            ];
        });
    }
}
