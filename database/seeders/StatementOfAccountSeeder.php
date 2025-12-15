<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StatementOfAccount;
use Illuminate\Support\Facades\DB;

class StatementOfAccountSeeder extends Seeder
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

        $waybillIds = DB::table('waybill_details')
            ->pluck('id')
            ->toArray();

        if (empty($shippingLineIds)) {
            $this->command->warn('Required shipping lines not found. Please seed shipping_lines first.');
            return;
        }

        $statementOfAccounts = [
            [
                'shipping_line_id' => $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-001',
                'soa_coverage_from' => now()->subDays(30)->toDateString(),
                'soa_coverage_to' => now()->subDays(1)->toDateString(),
                'waybill_id' => !empty($waybillIds) && count($waybillIds) >= 3
                    ? [$waybillIds[0], $waybillIds[1], $waybillIds[2]]
                    : [1, 2, 3],
                'signature' => false,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-002',
                'soa_coverage_from' => now()->subDays(60)->toDateString(),
                'soa_coverage_to' => now()->subDays(31)->toDateString(),
                'waybill_id' => !empty($waybillIds) && count($waybillIds) >= 5
                    ? [$waybillIds[3], $waybillIds[4]]
                    : [4, 5],
                'signature' => true,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-003',
                'soa_coverage_from' => now()->subDays(15)->toDateString(),
                'soa_coverage_to' => now()->toDateString(),
                'waybill_id' => !empty($waybillIds) && count($waybillIds) >= 7
                    ? [$waybillIds[5], $waybillIds[6]]
                    : [6, 7],
                'signature' => false,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-004',
                'soa_coverage_from' => now()->subDays(90)->toDateString(),
                'soa_coverage_to' => now()->subDays(61)->toDateString(),
                'waybill_id' => !empty($waybillIds) && count($waybillIds) >= 8
                    ? [$waybillIds[7]]
                    : [8],
                'signature' => true,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 2 ? $shippingLineIds[2] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-005',
                'soa_coverage_from' => now()->subDays(7)->toDateString(),
                'soa_coverage_to' => now()->toDateString(),
                'waybill_id' => !empty($waybillIds) && count($waybillIds) >= 9
                    ? [$waybillIds[8]]
                    : [9],
                'signature' => false,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 2 ? $shippingLineIds[2] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-006',
                'soa_coverage_from' => now()->subDays(45)->toDateString(),
                'soa_coverage_to' => now()->subDays(16)->toDateString(),
                'waybill_id' => !empty($waybillIds) && count($waybillIds) >= 6
                    ? [$waybillIds[0], $waybillIds[1], $waybillIds[2], $waybillIds[3], $waybillIds[4], $waybillIds[5]]
                    : [1, 2, 3, 4, 5, 6],
                'signature' => true,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 3 ? $shippingLineIds[3] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-007',
                'soa_coverage_from' => now()->subDays(21)->toDateString(),
                'soa_coverage_to' => now()->subDays(8)->toDateString(),
                'waybill_id' => !empty($waybillIds) && count($waybillIds) >= 4
                    ? [$waybillIds[0], $waybillIds[1], $waybillIds[2], $waybillIds[3]]
                    : [1, 2, 3, 4],
                'signature' => false,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 3 ? $shippingLineIds[3] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-008',
                'soa_coverage_from' => now()->subDays(120)->toDateString(),
                'soa_coverage_to' => now()->subDays(91)->toDateString(),
                'waybill_id' => !empty($waybillIds) && count($waybillIds) >= 2
                    ? [$waybillIds[0], $waybillIds[1]]
                    : [1, 2],
                'signature' => true,
            ],
        ];

        foreach ($statementOfAccounts as $soa) {
            StatementOfAccount::updateOrCreate(
                ['dli_sa_number' => $soa['dli_sa_number']],
                $soa
            );
        }
    }
}
