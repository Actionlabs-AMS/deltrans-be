<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StackRunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available IDs from related tables
        $shippingLineIds = DB::table('shipping_lines')
            ->pluck('id')
            ->toArray();

        $cypaIds = DB::table('cypa_details')
            ->pluck('id')
            ->toArray();

        if (empty($shippingLineIds) || empty($cypaIds) || count($cypaIds) < 2) {
            $this->command->warn('Required related records not found. Please seed shipping_lines and cypa_details first.');
            return;
        }

        $stackRuns = [
            [
                'reference_number' => 'SR-001',
                'shipping_line_id' => $shippingLineIds[0],
                'quantity_of_container' => 2,
                'container_size' => '20ft',
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'total_amount' => 8000.00,
                'is_complete' => 1,
            ],
            [
                'reference_number' => 'SR-002',
                'shipping_line_id' => $shippingLineIds[0],
                'quantity_of_container' => 1,
                'container_size' => '40ft',
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'total_amount' => 4000.00,
                'is_complete' => 1,
            ],
            [
                'reference_number' => 'SR-003',
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'quantity_of_container' => 3,
                'container_size' => '20ft',
                'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                'total_amount' => 12000.00,
                'is_complete' => 0,
            ],
            [
                'reference_number' => 'SR-004',
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'quantity_of_container' => 2,
                'container_size' => '40ft',
                'cypa_id_from' => $cypaIds[1],
                'cypa_id_to' => $cypaIds[0],
                'total_amount' => 8000.00,
                'is_complete' => 1,
            ],
            [
                'reference_number' => 'SR-005',
                'shipping_line_id' => count($shippingLineIds) > 2 ? $shippingLineIds[2] : $shippingLineIds[0],
                'quantity_of_container' => 1,
                'container_size' => '20ft',
                'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                'total_amount' => 4000.00,
                'is_complete' => 0,
            ],
        ];

        foreach ($stackRuns as $stackRun) {
            DB::table('stack_runs')->updateOrInsert(
                [
                    'shipping_line_id' => $stackRun['shipping_line_id'],
                    'cypa_id_from' => $stackRun['cypa_id_from'],
                    'cypa_id_to' => $stackRun['cypa_id_to'],
                    'container_size' => $stackRun['container_size'],
                ],
                array_merge($stackRun, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}

