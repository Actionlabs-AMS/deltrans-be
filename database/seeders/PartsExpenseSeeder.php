<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PartsExpense;
use App\Models\FleetTruck; // Make sure to import your Truck model
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PartsExpenseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get all existing plate numbers from your trucks table
        $plates = DB::table('fleet_trucks')->pluck('plate_number')->toArray();

        // 2. Safety check: If there are no trucks, we can't seed parts
        if (empty($plates)) {
            $this->command->warn("No trucks found in fleet_trucks. Seed trucks first!");
            return;
        }

        $start = Carbon::parse('2026-02-23');
        $end = Carbon::parse('2026-03-08');
        $period = CarbonPeriod::create($start, $end);
        $parts = ['Oil Filter', 'Tire', 'Brake Pad', 'Fan Belt', 'Wiper Blade'];

        foreach ($period as $date) {
            $entriesPerDay = rand(1, 2);

            for ($i = 0; $i < $entriesPerDay; $i++) {
                PartsExpense::create([
                    'shift' => rand(0, 1) ? 'Day' : 'Night',
                    // 3. Use a real plate number from the array
                    'plate_number' => $plates[array_rand($plates)], 
                    'receipt_no' => 'RCP-' . strtoupper(Str::random(5)),
                    'quantity' => rand(1, 4),
                    'article' => $parts[array_rand($parts)],
                    'amount_per_item' => rand(200, 1500),
                    'transaction_date' => $date->format('Y-m-d'),
                ]);
            }
        }
    }
}