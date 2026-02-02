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

        foreach ($waybills as $waybill) {
            // Determine number of containers based on container size
            // Size 40ft or 40: 1 container per waybill
            // Size 20ft or 20: 2 containers per waybill
            $containerSize = $waybill->container_size;
            // Handle both '40ft'/'20ft' format and '40'/'20' format
            $sizeValue = preg_replace('/[^0-9]/', '', $containerSize);
            $containersPerWaybill = ($sizeValue == '40') ? 1 : 2;

            // Create containers for this waybill
            for ($i = 0; $i < $containersPerWaybill; $i++) {
                $containerNumber = 'CONT-' . str_pad($containerCounter, 3, '0', STR_PAD_LEFT);

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

        // Also create containers for bookings that don't have waybills yet
        $bookingsWithoutContainers = DB::table('bookings')
            ->leftJoin('containers', 'bookings.id', '=', 'containers.booking_id')
            ->whereNull('containers.id')
            ->select('bookings.id')
            ->get();

        foreach ($bookingsWithoutContainers as $booking) {
            // Create 1-2 containers for bookings without waybills (default to size 20 behavior)
            $containersToCreate = 2;
            
            for ($i = 0; $i < $containersToCreate; $i++) {
                $containerNumber = 'CONT-' . str_pad($containerCounter, 3, '0', STR_PAD_LEFT);

                DB::table('containers')->updateOrInsert(
                    [
                        'booking_id' => $booking->id,
                        'container_number' => $containerNumber,
                    ],
                    [
                        'waybill_id' => null, // No waybill assigned yet
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
