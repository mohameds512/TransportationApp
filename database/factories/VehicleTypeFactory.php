<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VehicleType>
 */
class VehicleTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vehicleTypes = [
            'Sedan' => 4,
            'SUV' => 6,
            'Minivan' => 7,
            'Luxury' => 4,
            'Compact' => 4,
            'Electric' => 4,
        ];

        $type = fake()->unique()->randomElement(array_keys($vehicleTypes));

        return [
            'name' => $type,
            'capacity' => $vehicleTypes[$type],
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the vehicle type is a sedan.
     */
    public function sedan(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Sedan',
            'capacity' => 4,
            'description' => 'Standard sedan with room for 4 passengers',
        ]);
    }

    /**
     * Indicate that the vehicle type is an SUV.
     */
    public function suv(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'SUV',
            'capacity' => 6,
            'description' => 'Sport Utility Vehicle with room for 6 passengers',
        ]);
    }
}
