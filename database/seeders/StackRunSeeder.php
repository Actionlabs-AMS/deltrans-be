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

        $fixedExpenseIds = DB::table('fixed_expenses')
            ->pluck('id')
            ->toArray();

        $driverIds = DB::table('drivers')
            ->where('active_status', true)
            ->pluck('id')
            ->toArray();

        $fleetTruckIds = DB::table('fleet_trucks')
            ->where('status', 'Active')
            ->pluck('id')
            ->toArray();

        $requestIds = DB::table('requests')
            ->pluck('request_id')
            ->toArray();

        if (empty($shippingLineIds) || empty($driverIds) || empty($fleetTruckIds)) {
            $this->command->warn('Required related records not found. Please seed shipping_lines, drivers, and fleet_trucks first.');
            return;
        }

        $stackRuns = [
            [
                'shipping_line_id' => $shippingLineIds[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) ? $fixedExpenseIds[0] : null,
                'quantity_of_container' => 2,
                'drive_id' => $driverIds[0],
                'fleet_truck_id' => $fleetTruckIds[0],
                'waybill' => json_encode([
                    [
                        'waybill_id' => 1,
                        'container' => [
                            [
                                'container_id' => 1,
                                'pickup_date' => now()->subDays(2)->toDateString(),
                                'delivered_date' => now()->subDays(1)->toDateString(),
                            ],
                            [
                                'container_id' => 2,
                                'pickup_date' => now()->subDays(2)->toDateString(),
                                'delivered_date' => now()->subDays(1)->toDateString(),
                            ],
                        ],
                    ],
                ]),
                'other_expense' => !empty($requestIds) ? $requestIds[0] : null,
            ],
            [
                'shipping_line_id' => $shippingLineIds[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) ? $fixedExpenseIds[1] : null,
                'quantity_of_container' => 1,
                'drive_id' => $driverIds[1],
                'fleet_truck_id' => $fleetTruckIds[1],
                'waybill' => json_encode([
                    [
                        'waybill_id' => 2,
                        'container' => [
                            [
                                'container_id' => 3,
                                'pickup_date' => now()->subDays(3)->toDateString(),
                                'delivered_date' => now()->subDays(2)->toDateString(),
                            ],
                        ],
                    ],
                ]),
                'other_expense' => !empty($requestIds) ? $requestIds[1] : null,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 2 ? $fixedExpenseIds[2] : null,
                'quantity_of_container' => 3,
                'drive_id' => count($driverIds) > 2 ? $driverIds[2] : $driverIds[0],
                'fleet_truck_id' => count($fleetTruckIds) > 2 ? $fleetTruckIds[2] : $fleetTruckIds[0],
                'waybill' => json_encode([
                    [
                        'waybill_id' => 3,
                        'container' => [
                            [
                                'container_id' => 4,
                                'pickup_date' => now()->subDays(4)->toDateString(),
                                'delivered_date' => now()->subDays(3)->toDateString(),
                            ],
                            [
                                'container_id' => 5,
                                'pickup_date' => now()->subDays(4)->toDateString(),
                                'delivered_date' => now()->subDays(3)->toDateString(),
                            ],
                            [
                                'container_id' => 6,
                                'pickup_date' => now()->subDays(4)->toDateString(),
                                'delivered_date' => now()->subDays(3)->toDateString(),
                            ],
                        ],
                    ],
                ]),
                'other_expense' => !empty($requestIds) && count($requestIds) > 2 ? $requestIds[2] : null,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 3 ? $fixedExpenseIds[3] : null,
                'quantity_of_container' => 2,
                'drive_id' => count($driverIds) > 3 ? $driverIds[3] : $driverIds[1],
                'fleet_truck_id' => count($fleetTruckIds) > 3 ? $fleetTruckIds[3] : $fleetTruckIds[1],
                'waybill' => json_encode([
                    [
                        'waybill_id' => 4,
                        'container' => [
                            [
                                'container_id' => 7,
                                'pickup_date' => now()->subDays(5)->toDateString(),
                                'delivered_date' => now()->subDays(4)->toDateString(),
                            ],
                            [
                                'container_id' => 8,
                                'pickup_date' => now()->subDays(5)->toDateString(),
                                'delivered_date' => now()->subDays(4)->toDateString(),
                            ],
                        ],
                    ],
                ]),
                'other_expense' => null,
            ],
            [
                'shipping_line_id' => count($shippingLineIds) > 2 ? $shippingLineIds[2] : $shippingLineIds[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 4 ? $fixedExpenseIds[4] : null,
                'quantity_of_container' => 1,
                'drive_id' => count($driverIds) > 4 ? $driverIds[4] : $driverIds[0],
                'fleet_truck_id' => count($fleetTruckIds) > 4 ? $fleetTruckIds[4] : $fleetTruckIds[0],
                'waybill' => json_encode([
                    [
                        'waybill_id' => 5,
                        'container' => [
                            [
                                'container_id' => 9,
                                'pickup_date' => now()->subDays(6)->toDateString(),
                                'delivered_date' => now()->subDays(5)->toDateString(),
                            ],
                        ],
                    ],
                ]),
                'other_expense' => !empty($requestIds) && count($requestIds) > 3 ? $requestIds[3] : null,
            ],
        ];

        foreach ($stackRuns as $stackRun) {
            DB::table('stack_runs')->insert($stackRun);
        }
    }
}

