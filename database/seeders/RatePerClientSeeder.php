<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatePerClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates rate_per_clients for every (shipping_line_id, cypa_id, container_size)
     * so waybill lookups always find a match (no fallbacks).
     */
    public function run(): void
    {
        $shippingLineIds = DB::table('shipping_lines')->pluck('id')->toArray();
        $cypaIds = DB::table('cypa_details')->where('is_active', 1)->pluck('id')->toArray();

        if (empty($shippingLineIds)) {
            $this->command->warn('Required related records not found. Please seed shipping_lines first.');
            return;
        }

        $noOfDays = 7;
        $ratePerClients = [];

        foreach ($shippingLineIds as $lineIdx => $shippingLineId) {
            // cypa_id = 0 means "all CYPA" (used when no specific CYPA rate exists)
            $cypaIdsToSeed = array_merge([0], $cypaIds);
            foreach ($cypaIdsToSeed as $cypaIdx => $cypaId) {
                $variation = ($lineIdx + $cypaIdx) % 5;
                $ratePerClients[] = [
                    'shipping_line_id' => $shippingLineId,
                    'no_of_days' => $noOfDays,
                    'requirements' => 'Standard documentation',
                    'remarks' => $cypaId === 0 ? 'All CYPA' : 'CYPA specific',
                    'cypa_id' => $cypaId,
                    'stack_run' => 1500.00 + ($variation * 100),
                    'container_size' => '20ft',
                    'rate' => 5000.00 + ($variation * 200),
                    'is_active' => 1,
                ];
                $ratePerClients[] = [
                    'shipping_line_id' => $shippingLineId,
                    'no_of_days' => $noOfDays,
                    'requirements' => 'Standard documentation',
                    'remarks' => $cypaId === 0 ? 'All CYPA' : 'CYPA specific',
                    'cypa_id' => $cypaId,
                    'stack_run' => 2000.00 + ($variation * 150),
                    'container_size' => '40ft',
                    'rate' => 8000.00 + ($variation * 300),
                    'is_active' => 1,
                ];
            }
        }

        foreach ($ratePerClients as $ratePerClient) {
            $ratePerClient['tax_percent'] = $ratePerClient['tax_percent'] ?? null;
            DB::table('rate_per_clients')->updateOrInsert(
                [
                    'shipping_line_id' => $ratePerClient['shipping_line_id'],
                    'cypa_id' => $ratePerClient['cypa_id'],
                    'stack_run' => $ratePerClient['stack_run'],
                    'container_size' => $ratePerClient['container_size'],
                    'no_of_days' => $ratePerClient['no_of_days'],
                ],
                array_merge($ratePerClient, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Rate per clients seeded successfully.');
    }
}
