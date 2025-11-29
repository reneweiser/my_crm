<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => \App\Models\Client::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'position' => fake()->randomElement([
                'CEO',
                'CTO',
                'Project Manager',
                'Marketing Manager',
                'Sales Manager',
                'Managing Director',
                'Developer',
                'Designer',
                'Operations Manager',
            ]),
            'is_primary' => false,
        ];
    }

    /**
     * Indicate that the contact is the primary contact.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
