<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TripStatus>
 */
class TripStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = [
            'scheduled' => 'Trip is scheduled for the future',
            'in_progress' => 'Trip is currently in progress',
            'completed' => 'Trip has been completed successfully',
            'cancelled' => 'Trip has been cancelled',
        ];

        $status = fake()->unique()->randomElement(array_keys($statuses));

        return [
            'name' => $status,
            'description' => $statuses[$status],
        ];
    }

    /**
     * Indicate that the trip status is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'scheduled',
            'description' => 'Trip is scheduled for the future',
        ]);
    }

    /**
     * Indicate that the trip status is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'in_progress',
            'description' => 'Trip is currently in progress',
        ]);
    }

    /**
     * Indicate that the trip status is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'completed',
            'description' => 'Trip has been completed successfully',
        ]);
    }

    /**
     * Indicate that the trip status is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'cancelled',
            'description' => 'Trip has been cancelled',
        ]);
    }
}
