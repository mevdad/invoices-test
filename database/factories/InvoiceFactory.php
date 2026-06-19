<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $netAmount = $this->faker->randomFloat(2, 100, 10000);
        $vatAmount = round($netAmount * 0.2, 2);
        $issueDate = $this->faker->dateTimeBetween('-2 months', 'now');

        return [
            'number' => 'INV-'.$this->faker->unique()->numerify('######'),
            'supplier_name' => $this->faker->company(),
            'supplier_tax_id' => $this->faker->numerify('##########'),
            'net_amount' => $netAmount,
            'vat_amount' => $vatAmount,
            'gross_amount' => round($netAmount + $vatAmount, 2),
            'currency' => 'UAH',
            'status' => InvoiceStatus::Pending,
            'issue_date' => $issueDate,
            'due_date' => $this->faker->dateTimeBetween($issueDate, '+1 month'),
        ];
    }

    /**
     * Indicate that the invoice is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Pending,
        ]);
    }

    /**
     * Indicate that the invoice is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Approved,
        ]);
    }

    /**
     * Indicate that the invoice is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Rejected,
        ]);
    }
}
