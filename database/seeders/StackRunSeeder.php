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

        $waybillNumbers = DB::table('waybill_details')
            ->pluck('waybill_number')
            ->toArray();

        if (empty($shippingLineIds) || empty($cypaIds) || count($cypaIds) < 2) {
            $this->command->warn('Required related records not found. Please seed shipping_lines and cypa_details first.');
            return;
        }
        $stackRuns = [
            [
                'shipping_line_id' => $shippingLineIds[0],
                'quantity_of_container' => 2,
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'waybill' => !empty($waybillNumbers) ? json_encode([$waybillNumbers[0], $waybillNumbers[1]]) : json_encode(['WB-001', 'WB-002']),
                'total_amount' => 8000.00,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'quantity_of_container' => 1,
                'cypa_id_from' => $cypaIds[0],
                'cypa_id_to' => $cypaIds[1],
                'waybill' => !empty($waybillNumbers) && count($waybillNumbers) > 2 ? json_encode([$waybillNumbers[2]]) : json_encode(['WB-003']),
                'total_amount' => 4000.00,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'quantity_of_container' => 3,
                'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                'waybill' => !empty($waybillNumbers) && count($waybillNumbers) > 5 ? json_encode([$waybillNumbers[3], $waybillNumbers[4], $waybillNumbers[5]]) : json_encode(['WB-004', 'WB-005', 'WB-006']),
                'total_amount' => 12000.00,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'quantity_of_container' => 2,
                'cypa_id_from' => $cypaIds[1],
                'cypa_id_to' => $cypaIds[0],
                'waybill' => !empty($waybillNumbers) && count($waybillNumbers) > 7 ? json_encode([$waybillNumbers[6], $waybillNumbers[7]]) : json_encode(['WB-007', 'WB-008']),
                'total_amount' => 8000.00,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 2 ? $shippingLineIds[2] : $shippingLineIds[0],
                'quantity_of_container' => 1,
                'cypa_id_from' => count($cypaIds) > 2 ? $cypaIds[2] : $cypaIds[0],
                'cypa_id_to' => count($cypaIds) > 3 ? $cypaIds[3] : $cypaIds[1],
                'waybill' => !empty($waybillNumbers) && count($waybillNumbers) > 8 ? json_encode([$waybillNumbers[8]]) : json_encode(['WB-009']),
                'total_amount' => 4000.00,
            ],
        ];

        foreach ($stackRuns as $stackRun) {
            DB::table('stack_runs')->insert($stackRun);
        }
    }
}

