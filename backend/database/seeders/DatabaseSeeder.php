<?php

namespace Database\Seeders;

use App\Models\Invoice;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create seeds only if the table is empty
        if (Invoice::count() === 0) {
            Invoice::factory(20)->create();
        }
    }
}
