<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IssuedBudget;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class IssuedBudgetSeeder extends Seeder
{
    public function run(): void
    {
        // Define the date range
        $start = Carbon::parse('2026-02-23');
        $end = Carbon::parse('2026-03-08');
        
        // Create a period of days
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $currentDate = $date->format('Y-m-d');

            // Seed Day Shift
            IssuedBudget::create([
                'shift' => 'Day',
                'transaction_date' => $currentDate,
                'amount' => rand(5000, 15000), // Random amount for testing
                'source' => 'Office Cash',
            ]);

            // Seed Night Shift
            IssuedBudget::create([
                'shift' => 'Night',
                'transaction_date' => $currentDate,
                'amount' => rand(3000, 10000), // Random amount for testing
                'source' => 'Office Cash',
            ]);
        }
    }
}