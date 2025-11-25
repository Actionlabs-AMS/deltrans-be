<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requests = [
            [
                'extra_money' => 5000,
                'reason' => 'Additional fuel expenses due to traffic congestion',
                'type' => 1, // Expense request type
                'status' => 0, // Pending
            ],
            [
                'extra_money' => 3000,
                'reason' => 'Emergency repair costs for truck breakdown',
                'type' => 1,
                'status' => 1, // Approved
            ],
            [
                'extra_money' => 2000,
                'reason' => 'Overtime pay for helpers',
                'type' => 2, // Overtime request type
                'status' => 0, // Pending
            ],
            [
                'extra_money' => 1500,
                'reason' => 'Additional toll fees for alternative route',
                'type' => 1,
                'status' => 2, // Rejected
            ],
            [
                'extra_money' => 4000,
                'reason' => 'Extra loading/unloading charges',
                'type' => 1,
                'status' => 1, // Approved
            ],
            [
                'extra_money' => 2500,
                'reason' => 'Parking fees at port area',
                'type' => 1,
                'status' => 0, // Pending
            ],
        ];

        foreach ($requests as $request) {
            DB::table('requests')->insert($request);
        }
    }
}

