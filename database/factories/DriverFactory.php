<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'license_number' => 'LIC-' . fake()->unique()->numerify('#####'),
            'is_available' => fake()->boolean(80), // 80% chance of being available
            'current_latitude' => fake()->latitude(40.5, 41.0), // NYC area
            'current_longitude' => fake()->longitude(-74.5, -73.5), // NYC area
            'location_updated_at' => now(),
            'rating' => fake()->randomFloat(1, 3.0, 5.0),
        ];
    }

    /**
     * Indicate that the driver is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
        ]);
    }

    /**
     * Indicate that the driver is unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
