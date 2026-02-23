<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BudgetTransaction;
use App\Models\DriverCAHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DriverCAHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Cash advancement history per driver. Each record requires a budget_transaction (type 4).
     */
    public function run(): void
    {
        $driverIds = DB::table('drivers')->pluck('id')->toArray();
        if (empty($driverIds)) {
            $this->command->warn('No drivers found. Seed DriverSeeder first.');
            return;
        }

        $shifts = ['Day', 'Night'];
        $amounts = [100, 200, 500, 1000, 1500];

        foreach ($driverIds as $driverId) {
            for ($i = 0; $i < 5; $i++) {
                $shiftLabel = $shifts[array_rand($shifts)];
                $shiftValue = $shiftLabel === 'Night' ? BudgetTransaction::SHIFT_NIGHT : BudgetTransaction::SHIFT_MORNING;

                $budgetTransaction = BudgetTransaction::create([
                    'shift' => $shiftValue,
                    'transaction_type' => BudgetTransaction::TYPE_ADVANCE_EXPENSE,
                    'description' => 'Driver cash advance (seeded)',
                ]);

                DriverCAHistory::create([
                    'budget_transaction_id' => $budgetTransaction->id,
                    'driver_id' => $driverId,
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
