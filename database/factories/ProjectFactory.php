<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rateType = fake()->randomElement(['hourly', 'fixed', 'retainer']);
        $status = fake()->randomElement(['active', 'completed', 'archived']);

        return [
            'client_id' => \App\Models\Client::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'status' => $status,
            'rate_type' => $rateType,
            'hourly_rate' => $rateType === 'hourly' ? fake()->randomFloat(2, 50, 200) : null,
            'fixed_price' => $rateType === 'fixed' ? fake()->randomFloat(2, 1000, 10000) : null,
            'budget_hours' => fake()->optional()->numberBetween(10, 500),
            'start_date' => fake()->optional()->dateTimeBetween('-6 months', 'now'),
            'end_date' => $status === 'completed' ? fake()->dateTimeBetween('now', '+3 months') : null,
        ];
    }

    /**
     * Indicate that the project is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'end_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the project is hourly-based.
     */
    public function hourly(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_type' => 'hourly',
            'hourly_rate' => fake()->randomFloat(2, 50, 200),
            'fixed_price' => null,
        ]);
    }

    /**
     * Indicate that the project is fixed-price.
     */
    public function fixedPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_type' => 'fixed',
            'hourly_rate' => null,
            'fixed_price' => fake()->randomFloat(2, 1000, 10000),
        ]);
    }
}
