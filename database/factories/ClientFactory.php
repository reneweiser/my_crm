<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = fake()->company();

        return [
            'name' => fake()->name(),
            'company' => $company,
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'Germany',
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
