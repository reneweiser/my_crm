<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'user_id' => \App\Models\User::factory(),
            'description' => fake()->optional()->sentence(),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'hours' => fake()->randomFloat(2, 0.5, 8),
            'billable' => fake()->boolean(80), // 80% chance of being billable
            'invoiced' => false,
            'invoice_id' => null,
        ];
    }

    /**
     * Indicate that the time entry is billable.
     */
    public function billable(): static
    {
        return $this->state(fn (array $attributes) => [
            'billable' => true,
        ]);
    }

    /**
     * Indicate that the time entry is not billable.
     */
    public function nonBillable(): static
    {
        return $this->state(fn (array $attributes) => [
            'billable' => false,
        ]);
    }

    // /**
    //  * Indicate that the time entry has been invoiced.
    //  */
    // public function invoiced(): static
    // {
    //     return $this->state(fn (array $attributes) => [
    //         'invoiced' => true,
    //         'invoice_id' => \App\Models\Invoice::factory(),
    //     ]);
    // }
}
