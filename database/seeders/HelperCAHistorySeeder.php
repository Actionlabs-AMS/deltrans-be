<?php

namespace Database\Seeders;

use App\Models\HelperCAHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HelperCAHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Cash advancement history per helper. Shift is stored as string (e.g. Day, Night).
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

                HelperCAHistory::create([
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