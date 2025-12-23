<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatePerClientSeeder extends Seeder
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

        $stackRunIds = DB::table('stack_runs')
            ->pluck('id')
            ->toArray();

        if (empty($shippingLineIds)) {
            $this->command->warn('Required related records not found. Please seed shipping_lines first.');
            return;
        }

        $ratePerClients = [
            // Rates for all CYPA (cypa_id = 0)
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Standard documentation',
                'remarks' => 'Standard rate for 7 days',
                'cypa_id' => 0, // All CYPA
                'stack_run_id' => null,
                'size' => '20ft',
                'rate' => 5000,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Standard documentation',
                'remarks' => 'Standard rate for 7 days',
                'cypa_id' => 0, // All CYPA
                'stack_run_id' => null,
                'size' => '40ft',
                'rate' => 8000,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 14,
                'requirements' => 'Extended storage',
                'remarks' => 'Rate for 14 days storage',
                'cypa_id' => 0, // All CYPA
                'stack_run_id' => null,
                'size' => '20ft',
                'rate' => 7000,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 14,
                'requirements' => 'Extended storage',
                'remarks' => 'Rate for 14 days storage',
                'cypa_id' => 0, // All CYPA
                'stack_run_id' => null,
                'size' => '40ft',
                'rate' => 11000,
            ],
            // Offhire rates
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Offhire documentation',
                'remarks' => 'Offhire rate for 7 days',
                'cypa_id' => 0, // All CYPA
                'stack_run_id' => null,
                'size' => '20ft(offhire)',
                'rate' => 4500,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Offhire documentation',
                'remarks' => 'Offhire rate for 7 days',
                'cypa_id' => 0, // All CYPA
                'stack_run_id' => null,
                'size' => '40ft(offhire)',
                'rate' => 7500,
            ],
            // Rates for specific CYPA
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'CYPA specific rate',
                'remarks' => 'Rate for specific CYPA location',
                'cypa_id' => !empty($cypaIds) ? $cypaIds[0] : 0,
                'stack_run_id' => null,
                'size' => '20ft',
                'rate' => 4800,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'CYPA specific rate',
                'remarks' => 'Rate for specific CYPA location',
                'cypa_id' => !empty($cypaIds) ? $cypaIds[0] : 0,
                'stack_run_id' => null,
                'size' => '40ft',
                'rate' => 7800,
            ],
            // Rates for specific stack run
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Stack run specific rate',
                'remarks' => 'Rate for specific stack run',
                'cypa_id' => 0, // All CYPA
                'stack_run_id' => !empty($stackRunIds) ? $stackRunIds[0] : null,
                'size' => '20ft',
                'rate' => 5200,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'no_of_days' => 10,
                'requirements' => 'Special handling required',
                'remarks' => 'Rate for 10 days with special handling',
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 1 ? $cypaIds[1] : 0,
                'stack_run_id' => !empty($stackRunIds) && count($stackRunIds) > 1 ? $stackRunIds[1] : null,
                'size' => '40ft',
                'rate' => 9000,
            ],
        ];

        foreach ($ratePerClients as $ratePerClient) {
            DB::table('rate_per_clients')->updateOrInsert(
                [
                    'shipping_line_id' => $ratePerClient['shipping_line_id'],
                    'cypa_id' => $ratePerClient['cypa_id'],
                    'stack_run_id' => $ratePerClient['stack_run_id'],
                    'size' => $ratePerClient['size'],
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
