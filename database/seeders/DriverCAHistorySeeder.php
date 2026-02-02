<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DriverCAHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DriverCAHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Cash advancement history per driver. Uses actual driver IDs from drivers table.
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
                DriverCAHistory::create([
                    'driver_id' => $driverId,
                    'amount' => $amounts[array_rand($amounts)],
                    'shift' => $shifts[array_rand($shifts)],
                    'transaction_date' => Carbon::now()->subDays(rand(0, 30))->format('Y-m-d'),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
