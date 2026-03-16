<?php

namespace Database\Seeders;

use App\Models\DieselExpense;
use Illuminate\Database\Seeder;

class DieselExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample diesel_expenses with amount and purchase_order for testing.
     */
    public function run(): void
    {
        if (DieselExpense::count() > 0) {
            $this->command->info('Diesel expenses already present, skipping.');

            return;
        }

        $samples = [
            ['amount' => 1500.00, 'purchase_order' => 'PO-2025-001'],
            ['amount' => 2200.50, 'purchase_order' => 'PO-2025-002'],
            ['amount' => 1800.00, 'purchase_order' => null],
        ];

        foreach ($samples as $sample) {
            DieselExpense::create($sample);
        }

        $this->command->info('Diesel expenses seeded: ' . count($samples) . ' rows (amount, purchase_order).');
    }
}
