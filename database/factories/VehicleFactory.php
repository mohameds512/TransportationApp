<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $makes = ['Toyota', 'Honda', 'Ford', 'Chevrolet', 'Nissan', 'Tesla', 'BMW', 'Mercedes-Benz'];
        $models = [
            'Toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Prius'],
            'Honda' => ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey'],
            'Ford' => ['F-150', 'Escape', 'Explorer', 'Mustang', 'Edge'],
            'Chevrolet' => ['Silverado', 'Equinox', 'Malibu', 'Tahoe', 'Suburban'],
            'Nissan' => ['Altima', 'Rogue', 'Sentra', 'Pathfinder', 'Murano'],
            'Tesla' => ['Model 3', 'Model Y', 'Model S', 'Model X', 'Cybertruck'],
            'BMW' => ['3 Series', '5 Series', 'X3', 'X5', '7 Series'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'GLC', 'GLE', 'S-Class'],
        ];

        $colors = ['Black', 'White', 'Silver', 'Gray', 'Red', 'Blue', 'Green', 'Yellow', 'Brown'];

        $make = fake()->randomElement($makes);
        $model = fake()->randomElement($models[$make]);

        return [
            'driver_id' => Driver::factory(),
            'vehicle_type_id' => VehicleType::factory(),
            'license_plate' => strtoupper(fake()->unique()->bothify('??###??')),
            'make' => $make,
            'model' => $model,
            'year' => fake()->numberBetween(2015, 2024),
            'color' => fake()->randomElement($colors),
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'current_latitude' => fake()->latitude(40.5, 41.0), // NYC area
            'current_longitude' => fake()->longitude(-74.5, -73.5), // NYC area
            'location_updated_at' => now(),
        ];
    }

    /**
     * Indicate that the vehicle is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the vehicle is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the vehicle as a specific type.
     */
    public function ofType(string $typeName): static
    {
        return $this->state(function (array $attributes) use ($typeName) {
            $vehicleType = VehicleType::where('name', $typeName)->first();

            if (!$vehicleType) {
                $vehicleType = VehicleType::factory()->create(['name' => $typeName]);
            }

            return [
                'vehicle_type_id' => $vehicleType->id,
            ];
        });
    }
}
