<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $net = fake()->randomFloat(2, 1000, 50000);
        $vat = round($net * 0.20, 2); // 20% VAT
        $gross = round($net + $vat, 2);

        return [
            'number'          => 'INV-2026-' . fake()->unique()->numberBetween(10000, 99999),
            'supplier_name'   => fake()->company(),
            'supplier_tax_id' => fake()->numerify('##########'),
            'net_amount'      => $net,
            'vat_amount'      => $vat,
            'gross_amount'    => $gross,
            'currency'        => 'UAH',
            'status'          => fake()->randomElement(['pending', 'approved', 'rejected']),
            'issue_date'      => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'due_date'        => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        ];
    }
}
