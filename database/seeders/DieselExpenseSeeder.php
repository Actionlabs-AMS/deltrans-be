<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DieselExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];
        $now = Carbon::now();

        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                // Generates a random integer between 1000 and 10000
                'amount' => rand(1000, 10000), 
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Using chunk to insert for better performance
        foreach (array_chunk($data, 50) as $chunk) {
            DB::table('diesel_expenses')->insert($chunk);
        }
    }
}
