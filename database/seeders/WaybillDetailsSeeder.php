<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        $shippingLineEmails = DB::table('shipping_lines')
            ->pluck('email_address')
            ->toArray();

        $cypaIds = DB::table('cypa_details')
            ->pluck('id')
            ->toArray();

        $driverIds = DB::table('drivers')
            ->where('active_status', true)
            ->pluck('id')
            ->toArray();

        $helperIds = DB::table('helpers')
            ->where('active_status', true)
            ->pluck('id')
            ->toArray();

        $fleetTruckPlateNumbers = DB::table('fleet_trucks')
            ->where('status', 'Active')
            ->pluck('plate_number')
            ->toArray();

        $fixedExpenseIds = DB::table('fixed_expenses')
            ->pluck('id')
            ->toArray();

        $fixedExpensesMap = [];
        if (!empty($fixedExpenseIds)) {
            $fixedExpensesData = DB::table('fixed_expenses')
                ->select('id', 'container_size')
                ->get();
            foreach ($fixedExpensesData as $expense) {
                $fixedExpensesMap[$expense->id] = $expense->container_size;
            }
        }

        if (empty($shippingLineIds) || empty($driverIds) || empty($fleetTruckPlateNumbers)) {
            $this->command->warn('Required related records not found. Please seed shipping_lines, drivers, and fleet_trucks first.');
            return;
        }

        $waybillDetails = [
            [
                'waybill_number' => 'WB-001',
                'transaction_date' => now()->subDays(2)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com',
                'shipping_line_id' => $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) ? $cypaIds[0] : null,
                'driver_id' => $driverIds[0],
                'helper_id' => !empty($helperIds) ? $helperIds[0] : null,
                'truck_plate_number' => $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) ? $fixedExpenseIds[0] : null,
                'container_size' => !empty($fixedExpenseIds) && isset($fixedExpensesMap[$fixedExpenseIds[0]]) ? $fixedExpensesMap[$fixedExpenseIds[0]] : '20ft',
                'other_expense' => 500.00,
                'container_id' => 'CONT-001',
                'pickup_date' => now()->subDays(2)->toDateString(),
                'delivered_date' => now()->subDays(1)->toDateString(),
                'post_expense_amount' => 200.00,
                'total_amount' => 4700.00,
            ],
            [
                'waybill_number' => 'WB-002',
                'transaction_date' => now()->subDays(2)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com',
                'shipping_line_id' => $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) ? $cypaIds[1] : null,
                'driver_id' => count($driverIds) > 1 ? $driverIds[1] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 1 ? $helperIds[1] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 1 ? $fleetTruckPlateNumbers[1] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) ? $fixedExpenseIds[0] : null,
                'container_size' => !empty($fixedExpenseIds) && isset($fixedExpensesMap[$fixedExpenseIds[0]]) ? $fixedExpensesMap[$fixedExpenseIds[0]] : '20ft',
                'other_expense' => 0.00,
                'container_id' => 'CONT-002',
                'pickup_date' => now()->subDays(2)->toDateString(),
                'delivered_date' => now()->subDays(1)->toDateString(),
                'post_expense_amount' => 0.00,
                'total_amount' => 4000.00,
            ],
            [
                'waybill_number' => 'WB-003',
                'transaction_date' => now()->subDays(3)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com',
                'shipping_line_id' => $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 2 ? $cypaIds[2] : (!empty($cypaIds) ? $cypaIds[0] : null),
                'driver_id' => count($driverIds) > 2 ? $driverIds[2] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 2 ? $helperIds[2] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 2 ? $fleetTruckPlateNumbers[2] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 1 ? $fixedExpenseIds[1] : null,
                'container_size' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 1 && isset($fixedExpensesMap[$fixedExpenseIds[1]]) ? $fixedExpensesMap[$fixedExpenseIds[1]] : '40ft',
                'other_expense' => 750.00,
                'container_id' => 'CONT-003',
                'pickup_date' => now()->subDays(3)->toDateString(),
                'delivered_date' => now()->subDays(2)->toDateString(),
                'post_expense_amount' => 300.00,
                'total_amount' => 6800.00,
            ],
            [
                'waybill_number' => 'WB-004',
                'transaction_date' => now()->subDays(4)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) && count($shippingLineEmails) > 1 ? $shippingLineEmails[1] : (!empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com'),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) ? $cypaIds[0] : null,
                'driver_id' => count($driverIds) > 3 ? $driverIds[3] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 3 ? $helperIds[3] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 3 ? $fleetTruckPlateNumbers[3] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 2 ? $fixedExpenseIds[2] : null,
                'container_size' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 2 && isset($fixedExpensesMap[$fixedExpenseIds[2]]) ? $fixedExpensesMap[$fixedExpenseIds[2]] : '20ft',
                'other_expense' => 1000.00,
                'container_id' => 'CONT-004',
                'pickup_date' => now()->subDays(4)->toDateString(),
                'delivered_date' => now()->subDays(3)->toDateString(),
                'post_expense_amount' => 500.00,
                'total_amount' => 5500.00,
            ],
            [
                'waybill_number' => 'WB-005',
                'transaction_date' => now()->subDays(4)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) && count($shippingLineEmails) > 1 ? $shippingLineEmails[1] : (!empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com'),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 1 ? $cypaIds[1] : (!empty($cypaIds) ? $cypaIds[0] : null),
                'driver_id' => count($driverIds) > 4 ? $driverIds[4] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 4 ? $helperIds[4] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 4 ? $fleetTruckPlateNumbers[4] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 3 ? $fixedExpenseIds[3] : null,
                'container_size' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 3 && isset($fixedExpensesMap[$fixedExpenseIds[3]]) ? $fixedExpensesMap[$fixedExpenseIds[3]] : '40ft',
                'other_expense' => 0.00,
                'container_id' => 'CONT-005',
                'pickup_date' => now()->subDays(4)->toDateString(),
                'delivered_date' => now()->subDays(3)->toDateString(),
                'post_expense_amount' => 0.00,
                'total_amount' => 5750.00,
            ],
            [
                'waybill_number' => 'WB-006',
                'transaction_date' => now()->subDays(5)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) && count($shippingLineEmails) > 1 ? $shippingLineEmails[1] : (!empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com'),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 2 ? $cypaIds[2] : (!empty($cypaIds) ? $cypaIds[0] : null),
                'driver_id' => count($driverIds) > 1 ? $driverIds[1] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 1 ? $helperIds[1] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 1 ? $fleetTruckPlateNumbers[1] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 4 ? $fixedExpenseIds[4] : null,
                'container_size' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 4 && isset($fixedExpensesMap[$fixedExpenseIds[4]]) ? $fixedExpensesMap[$fixedExpenseIds[4]] : '20ft',
                'other_expense' => 250.00,
                'container_id' => 'CONT-006',
                'pickup_date' => now()->subDays(5)->toDateString(),
                'delivered_date' => now()->subDays(4)->toDateString(),
                'post_expense_amount' => 100.00,
                'total_amount' => 4750.00,
            ],
            [
                'waybill_number' => 'WB-007',
                'transaction_date' => now()->subDays(5)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com',
                'shipping_line_id' => $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) ? $cypaIds[1] : null,
                'driver_id' => count($driverIds) > 2 ? $driverIds[2] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 2 ? $helperIds[2] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 2 ? $fleetTruckPlateNumbers[2] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) ? $fixedExpenseIds[0] : null,
                'container_size' => !empty($fixedExpenseIds) && isset($fixedExpensesMap[$fixedExpenseIds[0]]) ? $fixedExpensesMap[$fixedExpenseIds[0]] : '20ft',
                'other_expense' => 0.00,
                'container_id' => 'CONT-007',
                'pickup_date' => now()->subDays(5)->toDateString(),
                'delivered_date' => now()->subDays(4)->toDateString(),
                'post_expense_amount' => 0.00,
                'total_amount' => 4000.00,
            ],
            [
                'waybill_number' => 'WB-008',
                'transaction_date' => now()->subDays(6)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com',
                'shipping_line_id' => $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) ? $cypaIds[0] : null,
                'driver_id' => count($driverIds) > 3 ? $driverIds[3] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 3 ? $helperIds[3] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 3 ? $fleetTruckPlateNumbers[3] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 1 ? $fixedExpenseIds[1] : null,
                'container_size' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 1 && isset($fixedExpensesMap[$fixedExpenseIds[1]]) ? $fixedExpensesMap[$fixedExpenseIds[1]] : '40ft',
                'other_expense' => 500.00,
                'container_id' => 'CONT-008',
                'pickup_date' => now()->subDays(6)->toDateString(),
                'delivered_date' => now()->subDays(5)->toDateString(),
                'post_expense_amount' => 200.00,
                'total_amount' => 6450.00,
            ],
            [
                'waybill_number' => 'WB-009',
                'transaction_date' => now()->subDays(6)->toDateString(),
                'shipping_line_email_address' => !empty($shippingLineEmails) && count($shippingLineEmails) > 1 ? $shippingLineEmails[1] : (!empty($shippingLineEmails) ? $shippingLineEmails[0] : 'shipping@example.com'),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'cypa_id' => !empty($cypaIds) && count($cypaIds) > 2 ? $cypaIds[2] : (!empty($cypaIds) ? $cypaIds[0] : null),
                'driver_id' => count($driverIds) > 4 ? $driverIds[4] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 4 ? $helperIds[4] : (!empty($helperIds) ? $helperIds[0] : null),
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 4 ? $fleetTruckPlateNumbers[4] : $fleetTruckPlateNumbers[0],
                'fixed_expense_id' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 2 ? $fixedExpenseIds[2] : null,
                'container_size' => !empty($fixedExpenseIds) && count($fixedExpenseIds) > 2 && isset($fixedExpensesMap[$fixedExpenseIds[2]]) ? $fixedExpensesMap[$fixedExpenseIds[2]] : '20ft',
                'other_expense' => 0.00,
                'container_id' => 'CONT-009',
                'pickup_date' => now()->subDays(6)->toDateString(),
                'delivered_date' => null, // Not yet delivered
                'post_expense_amount' => 0.00,
                'total_amount' => 4400.00,
            ],
        ];

        foreach ($waybillDetails as $waybill) {
            DB::table('waybill_details')->updateOrInsert(
                ['waybill_number' => $waybill['waybill_number']],
                $waybill
            );
        }
    }
}

