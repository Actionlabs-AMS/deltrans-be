<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TruckTripExpense;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class TruckTripExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::parse('2026-02-23');
        $end = Carbon::parse('2026-03-08');
        
        $period = CarbonPeriod::create($start, $end);

        $plates = \Illuminate\Support\Facades\DB::table('fleet_trucks')->where('is_active', 1)->pluck('plate_number')->toArray();
        $plateNumbers = $plates ?: ['ABC-1234', 'XYZ-5678', 'NCK-6498'];

        foreach ($period as $date) {
            $currentDate = $date->format('Y-m-d');
            $cashOnHand = rand(100, 500);
            $issuedCashAmount = rand(1000, 3000);

            TruckTripExpense::create([
                'shift' => $date->day % 2 == 0 ? 'Day' : 'Night',
                'plate_number' => $plateNumbers[array_rand($plateNumbers)],
                'helper_id' => rand(1, 5),
                'cash_on_hand' => $cashOnHand,
                'issued_cash_amount' => $issuedCashAmount,
                'remaining_amount' => $cashOnHand + $issuedCashAmount,
                'transaction_date' => $currentDate,
            ]);
        }
    }
}