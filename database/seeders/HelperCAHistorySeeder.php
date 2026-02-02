<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HelperCAHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HelperCAHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Cash advancement history per helper. Uses actual helper IDs from helpers table.
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
                HelperCAHistory::create([
                    'helper_id' => $helperId,
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