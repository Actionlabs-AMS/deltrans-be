<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContainerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get waybills with their container_size
        $waybills = DB::table('waybill_details')
            ->select(
                'waybill_details.id as waybill_id',
                'waybill_details.booking_id',
                'waybill_details.container_size'
            )
            ->get();

        if ($waybills->isEmpty()) {
            $this->command->warn('No waybills found. Please seed waybills first.');
            return;
        }

        $containerCounter = 1;
        $prefixes = ['MSCU', 'TEMU', 'CRLU', 'OOLU', 'HLCU', 'CMAU', 'ONEY', 'YMCU'];

        foreach ($waybills as $waybill) {
            $containerSize = $waybill->container_size;
            $sizeValue = preg_replace('/[^0-9]/', '', $containerSize);
            $containersPerWaybill = ($sizeValue == '40') ? 1 : 2;

            for ($i = 0; $i < $containersPerWaybill; $i++) {
                $prefix = $prefixes[($containerCounter - 1) % count($prefixes)];
                $containerNumber = $prefix . str_pad((string) $containerCounter, 7, '0', STR_PAD_LEFT);

                DB::table('containers')->updateOrInsert(
                    [
                        'booking_id' => $waybill->booking_id,
                        'container_number' => $containerNumber,
                    ],
                    [
                        'waybill_id' => $waybill->waybill_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $containerCounter++;
            }
        }

        $this->command->info('Containers seeded successfully.');
    }
}
