<?php

namespace Database\Seeders;

use App\Models\Invoice;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(int $count = 20): void
    {
        if (Invoice::count() > 0) {
            return;
        }

        // Generate random dates spread across the current year
        $dates = collect(range(1, $count))
            ->map(fn () => fake()->dateTimeBetween(date('Y-01-01'), 'now'))
            ->sort()
            ->values();

        // Track sequential number per date (INV-YYYY-MMDD-001, -002, ...)
        $seq = [];

        foreach ($dates as $date) {
            $key = $date->format('Y-m-d');
            $seq[$key] = ($seq[$key] ?? 0) + 1;

            $net = fake()->randomFloat(2, 1000, 50000);
            $vat = round($net * 0.20, 2);

            Invoice::factory()->create([
                'number'     => sprintf('INV-%s-%03d', $date->format('Y-md'), $seq[$key]),
                'issue_date' => $key,
                'due_date'   => fake()->dateTimeBetween($date, '+30 days')->format('Y-m-d'),
                'net_amount' => $net,
                'vat_amount' => $vat,
                'gross_amount' => round($net + $vat, 2),
                'created_at' => $date,
            ]);
        }
    }
}
