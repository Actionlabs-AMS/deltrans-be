<?php

namespace Database\Seeders;

use App\Models\BudgetTransaction;
use Illuminate\Database\Seeder;

class BudgetTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates budget_transactions with a mix of transaction types (0–4), random shift and description.
     */
    public function run(): void
    {
        $descriptionsByType = [
            BudgetTransaction::TYPE_ADD_BUDGET => [
                'Budget added',
                'Initial budget allocation',
                'Monthly top-up',
                'Budget replenishment',
                'Additional funds',
                'Opening balance',
            ],
            BudgetTransaction::TYPE_TRUCK_TRIP_EXPENSE => [
                'Truck trip to port',
                'Trip expense - North Harbor',
                'Fuel and toll for trip',
                'Truck trip (seeded)',
            ],
            BudgetTransaction::TYPE_PARTS_EXPENSE => [
                'Parts replacement',
                'Oil change parts',
                'Tire repair',
                'Maintenance parts (seeded)',
            ],
            BudgetTransaction::TYPE_FUNDS_FOR_STACK_RUN => [
                'Funds for stack run',
                'Stack run allocation',
                'Stack run (seeded)',
            ],
            BudgetTransaction::TYPE_ADVANCE_EXPENSE => [
                'Cash advance (seeded)',
                'Driver/helper advance',
            ],
        ];

        $types = [
            BudgetTransaction::TYPE_ADD_BUDGET,
            BudgetTransaction::TYPE_TRUCK_TRIP_EXPENSE,
            BudgetTransaction::TYPE_PARTS_EXPENSE,
            BudgetTransaction::TYPE_FUNDS_FOR_STACK_RUN,
            BudgetTransaction::TYPE_ADVANCE_EXPENSE,
        ];

        $count = 40;
        for ($i = 0; $i < $count; $i++) {
            $type = $types[array_rand($types)];
            $descriptions = $descriptionsByType[$type];
            $description = $descriptions[array_rand($descriptions)];

            BudgetTransaction::create([
                'shift' => rand(0, 1),
                'transaction_type' => $type,
                'description' => $description,
            ]);
        }

        $this->command->info("BudgetTransactionSeeder: created {$count} budget_transactions with mixed types.");
    }
}
