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


        if (empty($shippingLineIds)) {
            $this->command->warn('Required related records not found. Please seed shipping_lines first.');
            return;
        }

        $ratePerClients = [
            // Rates for all CYPA (cypa_id = 0) - These will be used as fallback
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Standard documentation',
                'remarks' => 'Standard rate for 7 days',
                'cypa_id' => 0, // All CYPA
                'stack_run' => 1500.00,
                'container_size' => '20ft',
                'rate' => 5500.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Standard documentation',
                'remarks' => 'Standard rate for 7 days',
                'cypa_id' => 0, // All CYPA
                'stack_run' => 2000.00,
                'container_size' => '40ft',
                'rate' => 8500.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 14,
                'requirements' => 'Extended storage',
                'remarks' => 'Rate for 14 days storage',
                'cypa_id' => 0, // All CYPA
                'stack_run' => 1800.00,
                'container_size' => '20ft',
                'rate' => 7200.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 14,
                'requirements' => 'Extended storage',
                'remarks' => 'Rate for 14 days storage',
                'cypa_id' => 0, // All CYPA
                'stack_run' => 2500.00,
                'container_size' => '40ft',
                'rate' => 11200.00,
                'is_active' => 1,
            ],
            // Offhire rates
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Offhire documentation',
                'remarks' => 'Offhire rate for 7 days',
                'cypa_id' => 0, // All CYPA
                'stack_run' => 1200.00,
                'container_size' => '20ft(offhire)',
                'rate' => 4800.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Offhire documentation',
                'remarks' => 'Offhire rate for 7 days',
                'cypa_id' => 0, // All CYPA
                'stack_run' => 1800.00,
                'container_size' => '40ft(offhire)',
                'rate' => 7800.00,
                'is_active' => 1,
            ],
            // Rates for specific CYPA locations (matching actual bookings)
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'CYPA specific rate',
                'remarks' => 'LOADING',
                'cypa_id' => !empty($cypaIds) ? $cypaIds[0] : 0,
                'stack_run' => 1500.00,
                'container_size' => '20ft',
                'rate' => 5200.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'CYPA specific rate',
                'remarks' => 'LOADING',
                'cypa_id' => !empty($cypaIds) ? $cypaIds[0] : 0,
                'stack_run' => 2000.00,
                'container_size' => '40ft',
                'rate' => 8200.00,
                'is_active' => 1,
            ],
            // Additional CYPA rates for other locations
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'CYPA specific rate',
                'remarks' => 'SHIP OUT',
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 1 ? $cypaIds[1] : 0,
                'stack_run' => 1600.00,
                'container_size' => '20ft',
                'rate' => 5300.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'CYPA specific rate',
                'remarks' => 'SHIP OUT',
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 1 ? $cypaIds[1] : 0,
                'stack_run' => 2100.00,
                'container_size' => '40ft',
                'rate' => 8300.00,
                'is_active' => 1,
            ],
            // Rates for other shipping lines
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Standard documentation',
                'remarks' => 'SHIP OUT',
                'cypa_id' => 0, // All CYPA
                'stack_run' => 1500.00,
                'container_size' => '20ft',
                'rate' => 5600.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'no_of_days' => 7,
                'requirements' => 'Standard documentation',
                'remarks' => 'SHIP OUT',
                'cypa_id' => 0, // All CYPA
                'stack_run' => 2000.00,
                'container_size' => '40ft',
                'rate' => 8800.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'no_of_days' => 10,
                'requirements' => 'Special handling required',
                'remarks' => 'EXPORT LOADED',
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 2 ? $cypaIds[2] : 0,
                'stack_run' => 2200.00,
                'container_size' => '20ft',
                'rate' => 6200.00,
                'is_active' => 1,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'no_of_days' => 10,
                'requirements' => 'Special handling required',
                'remarks' => 'EXPORT LOADED',
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 2 ? $cypaIds[2] : 0,
                'stack_run' => 2800.00,
                'container_size' => '40ft',
                'rate' => 9500.00,
                'is_active' => 1,
            ],
        ];

        // Additional rates for third shipping line if exists
        if (count($shippingLineIds) > 2) {
            $ratePerClients[] = [
                'shipping_line_id' => $shippingLineIds[2],
                'no_of_days' => 7,
                'requirements' => 'Standard documentation',
                'remarks' => 'LOADING',
                'cypa_id' => 0,
                'stack_run' => 1500.00,
                'container_size' => '20ft',
                'rate' => 5400.00,
                'is_active' => 1,
            ];
            $ratePerClients[] = [
                'shipping_line_id' => $shippingLineIds[2],
                'no_of_days' => 7,
                'requirements' => 'Standard documentation',
                'remarks' => 'LOADING',
                'cypa_id' => 0,
                'stack_run' => 2000.00,
                'container_size' => '40ft',
                'rate' => 8600.00,
                'is_active' => 1,
            ];
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
