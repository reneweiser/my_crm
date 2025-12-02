<?php

namespace Database\Factories;

use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    /**
     * Common service descriptions for realistic quote items
     */
    protected array $serviceDescriptions = [
        'Web Development',
        'Mobile App Development',
        'UI/UX Design',
        'Backend API Development',
        'Database Design & Implementation',
        'Frontend Development (React/Vue)',
        'Quality Assurance & Testing',
        'DevOps & Deployment Setup',
        'Technical Consulting',
        'Project Management',
        'Code Review & Refactoring',
        'Performance Optimization',
        'Security Audit',
        'Documentation Writing',
        'Training & Knowledge Transfer',
        'Bug Fixes & Maintenance',
        'Feature Enhancement',
        'Integration Services',
        'System Architecture Design',
        'Technical Support',
    ];

    /**
     * Common product descriptions
     */
    protected array $productDescriptions = [
        'Software License (1 year)',
        'Annual Maintenance Contract',
        'Cloud Hosting Service',
        'Domain Registration',
        'SSL Certificate',
        'Backup Service (monthly)',
        'CDN Service',
        'Email Hosting Package',
        'Premium Support Package',
        'Development Tools License',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isService = fake()->boolean(80); // 80% services, 20% products
        $descriptions = $isService ? $this->serviceDescriptions : $this->productDescriptions;
        $description = fake()->randomElement($descriptions);

        // Generate realistic quantities based on type
        if ($isService) {
            // Services: usually hours or days
            $quantity = fake()->randomFloat(2, 1, 200); // 1 to 200 hours
            $unit = fake()->randomElement(['hours', 'days']);

            // Adjust quantity for days
            if ($unit === 'days') {
                $quantity = fake()->randomFloat(2, 1, 40); // 1 to 40 days
            }
        } else {
            // Products: usually whole numbers
            $quantity = fake()->numberBetween(1, 12);
            $unit = fake()->randomElement(['piece', 'license', 'month', 'year']);
        }

        // Generate realistic prices (in smallest currency unit: cents)
        if ($isService) {
            // Hourly rates between €50-200/hour or daily rates €400-1600/day
            if ($unit === 'hours') {
                $unitPrice = fake()->numberBetween(5000, 20000); // €50-200/hour
            } else {
                $unitPrice = fake()->numberBetween(40000, 160000); // €400-1600/day
            }
        } else {
            // Product prices between €10-5000
            $unitPrice = fake()->numberBetween(1000, 500000); // €10-5000
        }

        // Calculate total (in smallest currency unit)
        $total = (int) ($quantity * $unitPrice);

        return [
            'quote_id' => Quote::factory(),
            'description' => $description,
            'quantity' => 1,
            'unit' => $unit,
            'unit_price' => 0,
            'total' => $total,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    /**
     * Create a service-based quote item (hourly/daily rate).
     */
    public function service(): static
    {
        return $this->state(function (array $attributes) {
            $description = fake()->randomElement($this->serviceDescriptions);
            $unit = fake()->randomElement(['hours', 'days']);
            $quantity = $unit === 'hours'
                ? fake()->randomFloat(2, 1, 200)
                : fake()->randomFloat(2, 1, 40);
            $unitPrice = $unit === 'hours'
                ? fake()->numberBetween(5000, 20000)
                : fake()->numberBetween(40000, 160000);
            $total = (int) ($quantity * $unitPrice);

            return [
                'description' => $description,
                'quantity' => $quantity,
                'unit' => $unit,
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        });
    }

    /**
     * Create a product-based quote item.
     */
    public function product(): static
    {
        return $this->state(function (array $attributes) {
            $description = fake()->randomElement($this->productDescriptions);
            $quantity = fake()->numberBetween(1, 12);
            $unit = fake()->randomElement(['piece', 'license', 'month', 'year']);
            $unitPrice = fake()->numberBetween(1000, 500000);
            $total = (int) ($quantity * $unitPrice);

            return [
                'description' => $description,
                'quantity' => $quantity,
                'unit' => $unit,
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        });
    }

    /**
     * Create an hourly-based service item.
     */
    public function hourly(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->randomFloat(2, 1, 200);
            $unitPrice = fake()->numberBetween(5000, 20000); // €50-200/hour
            $total = (int) ($quantity * $unitPrice);

            return [
                'quantity' => $quantity,
                'unit' => 'hours',
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        });
    }

    /**
     * Create a daily-based service item.
     */
    public function daily(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->randomFloat(2, 1, 40);
            $unitPrice = fake()->numberBetween(40000, 160000); // €400-1600/day
            $total = (int) ($quantity * $unitPrice);

            return [
                'quantity' => $quantity,
                'unit' => 'days',
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        });
    }

    /**
     * Set a specific position/sort order.
     */
    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $position,
        ]);
    }

    /**
     * Set specific price (in smallest currency unit).
     */
    public function withPrice(int $unitPrice): static
    {
        return $this->state(function (array $attributes) use ($unitPrice) {
            $quantity = $attributes['quantity'] ?? 1;
            $total = (int) ($quantity * $unitPrice);

            return [
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        });
    }

    /**
     * Set specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $unitPrice = $attributes['unit_price'] ?? 0;
            $total = (int) ($quantity * $unitPrice);

            return [
                'quantity' => $quantity,
                'total' => $total,
            ];
        });
    }
}
