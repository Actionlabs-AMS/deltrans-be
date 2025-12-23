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
        // Get stack runs
        $stackRuns = DB::table('stack_runs')
            ->select('id', 'quantity_of_container')
            ->get();

        if ($stackRuns->isEmpty()) {
            $this->command->warn('No stack runs found. Please seed stack_runs first.');
            return;
        }

        // Get available waybill numbers (optional)
        $waybillNumbers = DB::table('waybill_details')
            ->pluck('waybill_number')
            ->toArray();

        $containerCounter = 1;
        $waybillIndex = 0;

        foreach ($stackRuns as $stackRun) {
            // Create containers based on quantity_of_container
            for ($i = 0; $i < $stackRun->quantity_of_container; $i++) {
                $containerNumber = 'CONT-' . str_pad($containerCounter, 3, '0', STR_PAD_LEFT);

                // Assign waybill number if available (assign to some containers, not all)
                $waybillNumber = null;
                if (!empty($waybillNumbers) && $waybillIndex < count($waybillNumbers) && $i % 2 === 0) {
                    // Assign waybill to every other container
                    $waybillNumber = $waybillNumbers[$waybillIndex];
                    $waybillIndex++;
                }

                DB::table('containers')->updateOrInsert(
                    [
                        'stack_run_id' => $stackRun->id,
                        'container_number' => $containerNumber,
                    ],
                    [
                        'waybill_number' => $waybillNumber,
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
