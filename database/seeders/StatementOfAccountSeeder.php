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

        $waybillNumbers = DB::table('waybill_details')
            ->pluck('waybill_number')
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
                'waybill_id' => !empty($waybillNumbers) && count($waybillNumbers) >= 3
                    ? [$waybillNumbers[0], $waybillNumbers[1], $waybillNumbers[2]]
                    : null,
                'signature' => false,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-002',
                'soa_coverage_from' => now()->subDays(60)->toDateString(),
                'soa_coverage_to' => now()->subDays(31)->toDateString(),
                'waybill_id' => !empty($waybillNumbers) && count($waybillNumbers) >= 5
                    ? [$waybillNumbers[3], $waybillNumbers[4]]
                    : null,
                'signature' => true,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-003',
                'soa_coverage_from' => now()->subDays(15)->toDateString(),
                'soa_coverage_to' => now()->toDateString(),
                'waybill_id' => !empty($waybillNumbers) && count($waybillNumbers) >= 7
                    ? [$waybillNumbers[5], $waybillNumbers[6]]
                    : null,
                'signature' => false,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-004',
                'soa_coverage_from' => now()->subDays(90)->toDateString(),
                'soa_coverage_to' => now()->subDays(61)->toDateString(),
                'waybill_id' => !empty($waybillNumbers) && count($waybillNumbers) >= 8
                    ? [$waybillNumbers[7]]
                    : null,
                'signature' => true,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 2 ? $shippingLineIds[2] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-005',
                'soa_coverage_from' => now()->subDays(7)->toDateString(),
                'soa_coverage_to' => now()->toDateString(),
                'waybill_id' => !empty($waybillNumbers) && count($waybillNumbers) >= 9
                    ? [$waybillNumbers[8]]
                    : null,
                'signature' => false,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 2 ? $shippingLineIds[2] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-006',
                'soa_coverage_from' => now()->subDays(45)->toDateString(),
                'soa_coverage_to' => now()->subDays(16)->toDateString(),
                'waybill_id' => !empty($waybillNumbers) && count($waybillNumbers) >= 6
                    ? [$waybillNumbers[0], $waybillNumbers[1], $waybillNumbers[2], $waybillNumbers[3], $waybillNumbers[4], $waybillNumbers[5]]
                    : null,
                'signature' => true,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 3 ? $shippingLineIds[3] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-007',
                'soa_coverage_from' => now()->subDays(21)->toDateString(),
                'soa_coverage_to' => now()->subDays(8)->toDateString(),
                'waybill_id' => !empty($waybillNumbers) && count($waybillNumbers) >= 4
                    ? [$waybillNumbers[0], $waybillNumbers[1], $waybillNumbers[2], $waybillNumbers[3]]
                    : null,
                'signature' => false,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 3 ? $shippingLineIds[3] : $shippingLineIds[0],
                'dli_sa_number' => 'SA-2024-008',
                'soa_coverage_from' => now()->subDays(120)->toDateString(),
                'soa_coverage_to' => now()->subDays(91)->toDateString(),
                'waybill_id' => !empty($waybillNumbers) && count($waybillNumbers) >= 2
                    ? [$waybillNumbers[0], $waybillNumbers[1]]
                    : null,
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
