<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BudgetTransaction;
use App\Models\HelperCAHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HelperCAHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Cash advancement history per helper. Each record requires a budget_transaction (type 4).
     */
    public function run(): void
    {
        $helperIds = DB::table('helpers')->pluck('id')->toArray();
        if (empty($helperIds)) {
            $this->command->warn('No helpers found. Seed HelperSeeder first.');
            return;
        }

        $shifts = ['Day', 'Night'];
        $amounts = [100, 200, 500, 1000, 1500];

        foreach ($helperIds as $helperId) {
            for ($i = 0; $i < 5; $i++) {
                $shiftLabel = $shifts[array_rand($shifts)];
                $shiftValue = $shiftLabel === 'Night' ? BudgetTransaction::SHIFT_NIGHT : BudgetTransaction::SHIFT_MORNING;

                $budgetTransaction = BudgetTransaction::create([
                    'shift' => $shiftValue,
                    'transaction_type' => BudgetTransaction::TYPE_ADVANCE_EXPENSE,
                    'description' => 'Helper cash advance (seeded)',
                ]);

                HelperCAHistory::create([
                    'budget_transaction_id' => $budgetTransaction->id,
                    'helper_id' => $helperId,
                    'amount' => $amounts[array_rand($amounts)],
                    'shift' => $shiftLabel,
                    'transaction_date' => Carbon::now()->subDays(rand(0, 30))->format('Y-m-d'),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}