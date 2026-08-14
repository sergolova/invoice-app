<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween(date('Y-01-01'), 'now');
        $dueDate = fake()->dateTimeBetween($issueDate, '+1 month');

        $net = fake()->randomFloat(2, 1000, 50000);
        $vat = round($net * 0.20, 2);
        $gross = round($net + $vat, 2);

        return [
            'number'          => 'INV-' . $issueDate->format('Y-md') . '-' . fake()->unique()->numerify('###'),
            'supplier_name'   => fake()->company(),
            'supplier_tax_id' => fake()->numerify('##########'),
            'net_amount'      => $net,
            'vat_amount'      => $vat,
            'gross_amount'    => $gross,
            'currency'        => 'UAH',
            'status'          => fake()->randomElement(['pending', 'approved', 'rejected']),
            'issue_date'      => $issueDate->format('Y-m-d'),
            'due_date'        => $dueDate->format('Y-m-d'),
        ];
    }
}
