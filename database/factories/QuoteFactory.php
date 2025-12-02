<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['draft', 'sent', 'accepted', 'rejected', 'converted']);
        $validUntil = fake()->dateTimeBetween('now', '+60 days');

        // Generate sent_at if status is not draft
        $sentAt = in_array($status, ['sent', 'accepted', 'rejected', 'converted'])
            ? fake()->dateTimeBetween('-30 days', 'now')
            : null;

        // Generate accepted_at if status is accepted or converted
        $acceptedAt = in_array($status, ['accepted', 'converted']) && $sentAt
            ? fake()->dateTimeBetween($sentAt, 'now')
            : null;

        // Money stored as integers (smallest currency unit: cents)
        // These will be calculated based on quote items, but we set defaults
        $subtotal = fake()->numberBetween(50000, 2000000); // €500 to €20,000
        $taxRate = 1900; // 19.00% as basis points
        $taxAmount = (int) ($subtotal * ($taxRate / 10000));
        $total = $subtotal + $taxAmount;

        return [
            'client_id' => Client::factory(),
            'project_id' => fake()->boolean(70) ? Project::factory() : null,
            'quote_number' => $this->generateQuoteNumber(),
            'version' => fake()->numberBetween(1, 3),
            'status' => $status,
            'valid_until' => $validUntil,
            'sent_at' => $sentAt,
            'accepted_at' => $acceptedAt,
            'notes' => fake()->optional(0.3)->paragraph(),
            'client_notes' => fake()->optional(0.5)->sentence(),
//            'subtotal' => $subtotal,
//            'tax_rate' => $taxRate,
//            'tax_amount' => $taxAmount,
//            'total' => $total,
        ];
    }

    /**
     * Generate a quote number in format Q-YYYY-####
     */
    protected function generateQuoteNumber(): string
    {
        $year = fake()->dateTimeBetween('-2 years', 'now')->format('Y');
        $number = fake()->unique()->numberBetween(1, 9999);

        return sprintf('Q-%s-%04d', $year, $number);
    }

    /**
     * Indicate that the quote is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'sent_at' => null,
            'accepted_at' => null,
        ]);
    }

    /**
     * Indicate that the quote has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'accepted_at' => null,
        ]);
    }

    /**
     * Indicate that the quote has been accepted.
     */
    public function accepted(): static
    {
        $sentAt = fake()->dateTimeBetween('-30 days', '-1 day');

        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'sent_at' => $sentAt,
            'accepted_at' => fake()->dateTimeBetween($sentAt, 'now'),
        ]);
    }

    /**
     * Indicate that the quote has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'accepted_at' => null,
        ]);
    }

    /**
     * Indicate that the quote has been converted to an invoice.
     */
    public function converted(): static
    {
        $sentAt = fake()->dateTimeBetween('-90 days', '-30 days');
        $acceptedAt = fake()->dateTimeBetween($sentAt, '-20 days');

        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'sent_at' => $sentAt,
            'accepted_at' => $acceptedAt,
        ]);
    }

    /**
     * Set a specific tax rate (in basis points).
     *
     * @param int $rate Tax rate in basis points (e.g., 1900 = 19.00%)
     */
    public function withTaxRate(int $rate): static
    {
        return $this->state(function (array $attributes) use ($rate) {

            return [
                'tax_rate' => $rate,
            ];
        });
    }

    /**
     * Create quote with no tax (0% rate).
     */
    public function withoutTax(): static
    {
        return $this->withTaxRate(0);
    }

    /**
     * Create quote with reduced German VAT rate (7%).
     */
    public function withReducedVat(): static
    {
        return $this->withTaxRate(700);
    }
}
