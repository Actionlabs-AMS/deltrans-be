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

        foreach ($period as $date) {
            $currentDate = $date->format('Y-m-d');

            // We'll create one expense record per day
            // You can loop this if you want multiple trucks per day
            TruckTripExpense::create([
                'shift' => $date->day % 2 == 0 ? 'Day' : 'Night', // Alternates shift based on day
                'helper_id' => rand(1, 5), // Assumes you have helpers with IDs 1-5
                'cash_on_hand' => rand(100, 500),
                'issued_cash_amount' => rand(1000, 3000),
                'transaction_date' => $currentDate,
            ]);
        }
    }
}