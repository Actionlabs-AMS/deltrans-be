<?php

namespace Database\Seeders;

use App\Models\DieselExpense;
use Illuminate\Database\Seeder;

class DieselExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample diesel_expenses with amount, vishner_or, vishner_dr for testing.
     */
    public function run(): void
    {
        if (DieselExpense::count() > 0) {
            $this->command->info('Diesel expenses already present, skipping.');

            return;
        }

        $samples = [
            ['amount' => 1500.00, 'vishner_or' => 'OR-2025-001', 'vishner_dr' => 'DR-2025-001'],
            ['amount' => 2200.50, 'vishner_or' => 'OR-2025-002', 'vishner_dr' => 'DR-2025-002'],
            ['amount' => 1800.00, 'vishner_or' => null, 'vishner_dr' => 'DR-2025-003'],
        ];

        foreach ($samples as $sample) {
            DieselExpense::create($sample);
        }

        $this->command->info('Diesel expenses seeded: ' . count($samples) . ' rows (amount, vishner_or, vishner_dr).');
    }
}
