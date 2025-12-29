<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\WaybillDetail;

class WaybillDetailsSeeder extends Seeder
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

        $driverIds = DB::table('drivers')
            ->where('is_active', 1)
            ->pluck('id')
            ->toArray();

        $helperIds = DB::table('helpers')
            ->where('is_active', 1)
            ->pluck('id')
            ->toArray();

        $fleetTruckPlateNumbers = DB::table('fleet_trucks')
            ->where('is_active', 1)
            ->pluck('plate_number')
            ->toArray();

        $fixedExpenseIds = DB::table('fixed_expenses')
            ->pluck('id')
            ->toArray();

        $stackRunIds = DB::table('stack_runs')
            ->pluck('id')
            ->toArray();

        $ratePerClientIds = DB::table('rate_per_clients')
            ->pluck('id')
            ->toArray();

        if (empty($shippingLineIds) || empty($driverIds) || empty($fleetTruckPlateNumbers) || empty($stackRunIds) || empty($ratePerClientIds)) {
            $this->command->warn('Required related records not found. Please seed shipping_lines, drivers, fleet_trucks, stack_runs, and rate_per_clients first.');
            return;
        }

        $waybillDetails = [
            [
                'waybill_number' => 'WB-001',
                'transaction_date' => now()->subDays(2)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'stack_run_id' => $stackRunIds[0],
                'driver_id' => $driverIds[0],
                'helper_id' => !empty($helperIds) ? $helperIds[0] : null,
                'truck_plate_number' => $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) ? $fixedExpenseIds[0] : null,
                'rate_per_client_id' => $ratePerClientIds[0],
                'extra_money' => 500.00,
                'pickup_date' => now()->subDays(2)->toDateString(),
                'delivered_date' => now()->subDays(1)->toDateString(),
                'post_expense_amount' => 200.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
            [
                'waybill_number' => 'WB-002',
                'transaction_date' => now()->subDays(2)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'stack_run_id' => count($stackRunIds) > 1 ? $stackRunIds[1] : $stackRunIds[0],
                'driver_id' => count($driverIds) > 1 ? $driverIds[1] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 1 ? $helperIds[1] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 1 ? $fleetTruckPlateNumbers[1] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) ? $fixedExpenseIds[0] : null,
                'rate_per_client_id' => count($ratePerClientIds) > 1 ? $ratePerClientIds[1] : $ratePerClientIds[0],
                'extra_money' => 0.00,
                'pickup_date' => now()->subDays(2)->toDateString(),
                'delivered_date' => now()->subDays(1)->toDateString(),
                'post_expense_amount' => 0.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
            [
                'waybill_number' => 'WB-003',
                'transaction_date' => now()->subDays(3)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'stack_run_id' => count($stackRunIds) > 2 ? $stackRunIds[2] : $stackRunIds[0],
                'driver_id' => count($driverIds) > 2 ? $driverIds[2] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 2 ? $helperIds[2] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 2 ? $fleetTruckPlateNumbers[2] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 1 ? $fixedExpenseIds[1] : null,
                'rate_per_client_id' => count($ratePerClientIds) > 2 ? $ratePerClientIds[2] : $ratePerClientIds[0],
                'extra_money' => 750.00,
                'pickup_date' => now()->subDays(3)->toDateString(),
                'delivered_date' => now()->subDays(2)->toDateString(),
                'post_expense_amount' => 300.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
            [
                'waybill_number' => 'WB-004',
                'transaction_date' => now()->subDays(4)->toDateString(),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'stack_run_id' => count($stackRunIds) > 3 ? $stackRunIds[3] : $stackRunIds[0],
                'driver_id' => count($driverIds) > 3 ? $driverIds[3] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 3 ? $helperIds[3] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 3 ? $fleetTruckPlateNumbers[3] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 2 ? $fixedExpenseIds[2] : null,
                'rate_per_client_id' => count($ratePerClientIds) > 3 ? $ratePerClientIds[3] : $ratePerClientIds[0],
                'extra_money' => 1000.00,
                'pickup_date' => now()->subDays(4)->toDateString(),
                'delivered_date' => now()->subDays(3)->toDateString(),
                'post_expense_amount' => 500.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
            [
                'waybill_number' => 'WB-005',
                'transaction_date' => now()->subDays(4)->toDateString(),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'stack_run_id' => count($stackRunIds) > 4 ? $stackRunIds[4] : $stackRunIds[0],
                'driver_id' => count($driverIds) > 4 ? $driverIds[4] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 4 ? $helperIds[4] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 4 ? $fleetTruckPlateNumbers[4] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 3 ? $fixedExpenseIds[3] : null,
                'rate_per_client_id' => count($ratePerClientIds) > 4 ? $ratePerClientIds[4] : $ratePerClientIds[0],
                'extra_money' => 0.00,
                'pickup_date' => now()->subDays(4)->toDateString(),
                'delivered_date' => now()->subDays(3)->toDateString(),
                'post_expense_amount' => 0.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
            [
                'waybill_number' => 'WB-006',
                'transaction_date' => now()->subDays(5)->toDateString(),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'stack_run_id' => count($stackRunIds) > 1 ? $stackRunIds[1] : $stackRunIds[0],
                'driver_id' => count($driverIds) > 1 ? $driverIds[1] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 1 ? $helperIds[1] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 1 ? $fleetTruckPlateNumbers[1] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 4 ? $fixedExpenseIds[4] : null,
                'rate_per_client_id' => count($ratePerClientIds) > 1 ? $ratePerClientIds[1] : $ratePerClientIds[0],
                'extra_money' => 250.00,
                'pickup_date' => now()->subDays(5)->toDateString(),
                'delivered_date' => now()->subDays(4)->toDateString(),
                'post_expense_amount' => 100.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
            [
                'waybill_number' => 'WB-007',
                'transaction_date' => now()->subDays(5)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'stack_run_id' => $stackRunIds[0],
                'driver_id' => count($driverIds) > 2 ? $driverIds[2] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 2 ? $helperIds[2] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 2 ? $fleetTruckPlateNumbers[2] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) ? $fixedExpenseIds[0] : null,
                'rate_per_client_id' => count($ratePerClientIds) > 2 ? $ratePerClientIds[2] : $ratePerClientIds[0],
                'extra_money' => 0.00,
                'pickup_date' => now()->subDays(5)->toDateString(),
                'delivered_date' => now()->subDays(4)->toDateString(),
                'post_expense_amount' => 0.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
            [
                'waybill_number' => 'WB-008',
                'transaction_date' => now()->subDays(6)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'stack_run_id' => count($stackRunIds) > 1 ? $stackRunIds[1] : $stackRunIds[0],
                'driver_id' => count($driverIds) > 3 ? $driverIds[3] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 3 ? $helperIds[3] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 3 ? $fleetTruckPlateNumbers[3] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 1 ? $fixedExpenseIds[1] : null,
                'rate_per_client_id' => count($ratePerClientIds) > 3 ? $ratePerClientIds[3] : $ratePerClientIds[0],
                'extra_money' => 500.00,
                'pickup_date' => now()->subDays(6)->toDateString(),
                'delivered_date' => now()->subDays(5)->toDateString(),
                'post_expense_amount' => 200.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
            [
                'waybill_number' => 'WB-009',
                'transaction_date' => now()->subDays(6)->toDateString(),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'stack_run_id' => count($stackRunIds) > 2 ? $stackRunIds[2] : $stackRunIds[0],
                'driver_id' => count($driverIds) > 4 ? $driverIds[4] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 4 ? $helperIds[4] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 4 ? $fleetTruckPlateNumbers[4] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 2 ? $fixedExpenseIds[2] : null,
                'rate_per_client_id' => count($ratePerClientIds) > 4 ? $ratePerClientIds[4] : $ratePerClientIds[0],
                'extra_money' => 0.00,
                'pickup_date' => now()->subDays(6)->toDateString(),
                'delivered_date' => now()->subDays(5)->toDateString(), // Pending delivery
                'post_expense_amount' => 0.00,
                // total_rate_per_client and total_expense will be auto-calculated
            ],
        ];

        foreach ($waybillDetails as $waybill) {
            // Use model to ensure auto-calculation works
            WaybillDetail::updateOrCreate(
                ['waybill_number' => $waybill['waybill_number']],
                $waybill
            );
        }
    }
}

