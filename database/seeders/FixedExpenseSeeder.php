<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixedExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available cypa_detail IDs
        $cypaIds = DB::table('cypa_details')
            ->pluck('id')
            ->toArray();

        if (empty($cypaIds) || count($cypaIds) < 2) {
            $this->command->warn('Not enough cypa_details records found. Please seed cypa_details first.');
            return;
        }

        $fixedExpenses = [
            [
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'size' => '20ft',
                'docs_fee' => 500,
                'stack_run' => 1500,
                'expenses' => 2000,
                'total_expenses' => 4000,
            ],
            [
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'size' => '40ft',
                'docs_fee' => 750,
                'stack_run' => 2000,
                'expenses' => 3000,
                'total_expenses' => 5750,
            ],
            [
                'cypa_id_from' => $cypaIds[1],
                'cypa_id_to' => $cypaIds[0],
                'size' => '20ft',
                'docs_fee' => 500,
                'stack_run' => 1500,
                'expenses' => 2000,
                'total_expenses' => 4000,
            ],
            [
                'cypa_id_from' => $cypaIds[1],
                'cypa_id_to' => $cypaIds[0],
                'size' => '40ft',
                'docs_fee' => 750,
                'stack_run' => 2000,
                'expenses' => 3000,
                'total_expenses' => 5750,
            ],
            [
                'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                'size' => '20ft',
                'docs_fee' => 600,
                'stack_run' => 1600,
                'expenses' => 2200,
                'total_expenses' => 4400,
            ],
        ];

        foreach ($fixedExpenses as $expense) {
            DB::table('fixed_expenses')->updateOrInsert(
                [
                    'cypa_id_from' => $expense['cypa_id_from'],
                    'cypa_id_to' => $expense['cypa_id_to'],
                    'size' => $expense['size'],
                ],
                $expense
            );
        }
    }
}

