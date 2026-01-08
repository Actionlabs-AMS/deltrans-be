<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DriverCAHistory; 
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DriverCAHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = ['Day', 'Night'];
        $amounts = [100, 200, 500, 1000, 1500];

        // Loop through your existing driver IDs 1 to 10
        for ($driverId = 1; $driverId <= 10; $driverId++) {
            
            // Generate 5 random history records per driver
            for ($i = 0; $i < 5; $i++) {
                DriverCAHistory::create([
                    'driver_id' => $driverId,
                    'amount' => $amounts[array_rand($amounts)],
                    'shift' => $shifts[array_rand($shifts)],
                    // Creates random dates within the last 30 days
                    'transaction_date' => Carbon::now()->subDays(rand(0, 30))->format('Y-m-d'),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
