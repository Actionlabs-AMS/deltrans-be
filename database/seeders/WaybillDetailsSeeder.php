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

        $bookingIds = DB::table('bookings')
            ->pluck('id')
            ->toArray();

        $ratePerClientIds = DB::table('rate_per_clients')
            ->pluck('id')
            ->toArray();

        $bookings = DB::table('bookings')
            ->select('id', 'shipping_line_id', 'cypa_id_from', 'cypa_id_to')
            ->get()
            ->keyBy('id');

        $driversWithHelpers = DB::table('drivers')
            ->select('id', 'helpers_id')
            ->get()
            ->keyBy('id');

        if (empty($shippingLineIds) || empty($driverIds) || empty($fleetTruckPlateNumbers) || empty($bookingIds) || empty($ratePerClientIds)) {
            $this->command->warn('Required related records not found. Please seed shipping_lines, drivers, fleet_trucks, bookings, and rate_per_clients first.');
            return;
        }

        // Helper function to find fixed_expense_id based on booking and container_size
        // Match: shipping_line_id (booking) + cypa_id_from (booking) + cypa_id_to (booking) + container_size (waybill)
        $findFixedExpenseId = function ($bookingId, $containerSize) use ($bookings) {
            if (!isset($bookings[$bookingId])) {
                return null;
            }

            $booking = $bookings[$bookingId];

            $fixedExpense = DB::table('fixed_expenses')
                ->where('shipping_line_id', $booking->shipping_line_id)
                ->where('cypa_id_from', $booking->cypa_id_from)
                ->where('cypa_id_to', $booking->cypa_id_to)
                ->where('container_size', $containerSize)
                ->first();

            return $fixedExpense ? $fixedExpense->id : null;
        };

        // Helper function to find rate_per_client_id based on booking and container_size
        // Match: shipping_line_id (booking) + cypa_id_from (booking) = cypa_id (rate_per_client) + container_size (waybill)
        // Falls back to cypa_id = 0 (all CYPA) if specific match not found
        $findRatePerClientId = function ($bookingId, $containerSize) use ($bookings) {
            if (!isset($bookings[$bookingId])) {
                return null;
            }

            $booking = $bookings[$bookingId];

            // First try to find specific CYPA match
            $ratePerClient = DB::table('rate_per_clients')
                ->where('shipping_line_id', $booking->shipping_line_id)
                ->where('cypa_id', $booking->cypa_id_from)
                ->where('container_size', $containerSize)
                ->where('is_active', 1)
                ->first();

            // If not found, try cypa_id = 0 (all CYPA) as fallback
            if (!$ratePerClient) {
                $ratePerClient = DB::table('rate_per_clients')
                    ->where('shipping_line_id', $booking->shipping_line_id)
                    ->where('cypa_id', 0) // All CYPA
                    ->where('container_size', $containerSize)
                    ->where('is_active', 1)
                    ->first();
            }

            return $ratePerClient ? $ratePerClient->id : null;
        };

        $waybillDetails = [
            [
                'waybill_number' => 'WB-001',
                'transaction_date' => now()->subDays(2)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'booking_id' => $bookingIds[0],
                'driver_id' => $driverIds[0],
                'helper_id' => !empty($helperIds) ? [$helperIds[0]] : null,
                'container_size' => '20ft',
                'container_type' => 'DRY',
                'truck_plate_number' => $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(2)->toDateString(),
                'delivered_date' => now()->subDays(1)->toDateString(),
                'post_expense_amount' => 200.00,
            ],
            [
                'waybill_number' => 'WB-002',
                'transaction_date' => now()->subDays(2)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'booking_id' => count($bookingIds) > 1 ? $bookingIds[1] : $bookingIds[0],
                'driver_id' => count($driverIds) > 1 ? $driverIds[1] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 1 ? [$helperIds[1]] : (!empty($helperIds) ? [$helperIds[0]] : null),
                'container_size' => '40ft',
                'container_type' => 'DRY',
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 1 ? $fleetTruckPlateNumbers[1] : $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(2)->toDateString(),
                'delivered_date' => now()->subDays(1)->toDateString(),
                'post_expense_amount' => 0.00,
            ],
            [
                'waybill_number' => 'WB-003',
                'transaction_date' => now()->subDays(3)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'booking_id' => count($bookingIds) > 2 ? $bookingIds[2] : $bookingIds[0],
                'driver_id' => count($driverIds) > 2 ? $driverIds[2] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 2 ? [$helperIds[2]] : (!empty($helperIds) ? [$helperIds[0]] : null),
                'container_size' => '20ft',
                'container_type' => 'REEFER',
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 2 ? $fleetTruckPlateNumbers[2] : $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(3)->toDateString(),
                'delivered_date' => now()->subDays(2)->toDateString(),
                'post_expense_amount' => 300.00,
            ],
            [
                'waybill_number' => 'WB-004',
                'transaction_date' => now()->subDays(4)->toDateString(),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'booking_id' => count($bookingIds) > 3 ? $bookingIds[3] : $bookingIds[0],
                'driver_id' => count($driverIds) > 3 ? $driverIds[3] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 3 ? [$helperIds[3]] : (!empty($helperIds) ? [$helperIds[0]] : null),
                'container_size' => '40ft',
                'container_type' => 'DRY',
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 3 ? $fleetTruckPlateNumbers[3] : $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(4)->toDateString(),
                'delivered_date' => now()->subDays(3)->toDateString(),
                'post_expense_amount' => 500.00,
            ],
            [
                'waybill_number' => 'WB-005',
                'transaction_date' => now()->subDays(4)->toDateString(),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'booking_id' => count($bookingIds) > 4 ? $bookingIds[4] : $bookingIds[0],
                'driver_id' => count($driverIds) > 4 ? $driverIds[4] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 4 ? [$helperIds[4]] : (!empty($helperIds) ? [$helperIds[0]] : null),
                'container_size' => '20ft',
                'container_type' => 'DRY',
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 4 ? $fleetTruckPlateNumbers[4] : $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(4)->toDateString(),
                'delivered_date' => now()->subDays(3)->toDateString(),
                'post_expense_amount' => 0.00,
            ],
            [
                'waybill_number' => 'WB-006',
                'transaction_date' => now()->subDays(5)->toDateString(),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'booking_id' => count($bookingIds) > 1 ? $bookingIds[1] : $bookingIds[0],
                'driver_id' => count($driverIds) > 1 ? $driverIds[1] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 1 ? [$helperIds[1], $helperIds[0]] : (!empty($helperIds) ? [$helperIds[0]] : null), // Multiple helpers example
                'container_size' => '40ft',
                'container_type' => 'REEFER',
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 1 ? $fleetTruckPlateNumbers[1] : $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(5)->toDateString(),
                'delivered_date' => now()->subDays(4)->toDateString(),
                'post_expense_amount' => 100.00,
            ],
            [
                'waybill_number' => 'WB-007',
                'transaction_date' => now()->subDays(5)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'booking_id' => $bookingIds[0],
                'driver_id' => count($driverIds) > 2 ? $driverIds[2] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 2 ? [$helperIds[2]] : (!empty($helperIds) ? [$helperIds[0]] : null),
                'container_size' => '20ft',
                'container_type' => 'DRY',
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 2 ? $fleetTruckPlateNumbers[2] : $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(5)->toDateString(),
                'delivered_date' => now()->subDays(4)->toDateString(),
                'post_expense_amount' => 0.00,
            ],
            [
                'waybill_number' => 'WB-008',
                'transaction_date' => now()->subDays(6)->toDateString(),
                'shipping_line_id' => $shippingLineIds[0],
                'booking_id' => count($bookingIds) > 1 ? $bookingIds[1] : $bookingIds[0],
                'driver_id' => count($driverIds) > 3 ? $driverIds[3] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 3 ? [$helperIds[3]] : (!empty($helperIds) ? [$helperIds[0]] : null),
                'container_size' => '40ft',
                'container_type' => 'DRY',
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 3 ? $fleetTruckPlateNumbers[3] : $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(6)->toDateString(),
                'delivered_date' => now()->subDays(5)->toDateString(),
                'post_expense_amount' => 200.00,
            ],
            [
                'waybill_number' => 'WB-009',
                'transaction_date' => now()->subDays(6)->toDateString(),
                'shipping_line_id' => count($shippingLineIds) > 1 ? $shippingLineIds[1] : $shippingLineIds[0],
                'booking_id' => count($bookingIds) > 2 ? $bookingIds[2] : $bookingIds[0],
                'driver_id' => count($driverIds) > 4 ? $driverIds[4] : $driverIds[0],
                'helper_id' => !empty($helperIds) && count($helperIds) > 4 ? [$helperIds[4]] : (!empty($helperIds) ? [$helperIds[0]] : null),
                'container_size' => '20ft',
                'container_type' => 'REEFER',
                'truck_plate_number' => count($fleetTruckPlateNumbers) > 4 ? $fleetTruckPlateNumbers[4] : $fleetTruckPlateNumbers[0],
                'pickup_date' => now()->subDays(6)->toDateString(),
                'delivered_date' => now()->subDays(5)->toDateString(), // Pending delivery
                'post_expense_amount' => 0.00,
            ],
        ];

        // Get all fixed expense IDs as fallback
        $fixedExpenseIds = DB::table('fixed_expenses')->pluck('id')->toArray();

        if (empty($fixedExpenseIds)) {
            $this->command->warn('No fixed expenses found. Please seed fixed_expenses first.');
            return;
        }

        foreach ($waybillDetails as $waybill) {
            $booking = $bookings[$waybill['booking_id']] ?? null;

            if (!$booking) {
                $this->command->warn("Booking {$waybill['booking_id']} not found. Skipping waybill {$waybill['waybill_number']}.");
                continue;
            }

            $driver = $driversWithHelpers[$waybill['driver_id']] ?? null;
            if ($driver && $driver->helpers_id) {
                $driverHelpers = is_string($driver->helpers_id) ? json_decode($driver->helpers_id, true) : $driver->helpers_id;
                $waybill['helper_id'] = is_array($driverHelpers) && !empty($driverHelpers) ? [$driverHelpers[0]] : null;
            } else {
                $waybill['helper_id'] = null;
            }

            // Find fixed_expense_id based on booking (shipping_line_id, cypa_id_from, cypa_id_to) and container_size
            $fixedExpenseId = $findFixedExpenseId($waybill['booking_id'], $waybill['container_size']);

            if (!$fixedExpenseId) {
                // Last resort: use the first available fixed expense
                $fixedExpenseId = $fixedExpenseIds[0];
                $this->command->warn("No matching fixed expense found for booking {$waybill['booking_id']} (shipping_line: {$booking->shipping_line_id}, cypa_from: {$booking->cypa_id_from}, cypa_to: {$booking->cypa_id_to}, container: {$waybill['container_size']}). Using fallback fixed_expense_id: {$fixedExpenseId}");
            }

            // Get fixed expense to calculate total_expense
            $fixedExpense = DB::table('fixed_expenses')->find($fixedExpenseId);
            $totalExpense = ($waybill['post_expense_amount'] ?? 0) + ($fixedExpense->total_expenses ?? 0);

            // Find rate_per_client_id based on booking (shipping_line_id, cypa_id_from) and container_size
            $ratePerClientId = $findRatePerClientId($waybill['booking_id'], $waybill['container_size']);

            // Get rate_per_client to calculate total_rate_per_client
            $totalRatePerClient = 0.00;
            if ($ratePerClientId) {
                $ratePerClient = DB::table('rate_per_clients')->find($ratePerClientId);
                $totalRatePerClient = $ratePerClient->rate ?? 0.00;
            } else {
                // If no rate_per_client found, try to find a matching one for display
                // This ensures we have realistic amounts even if matching failed
                $fallbackRate = DB::table('rate_per_clients')
                    ->where('shipping_line_id', $booking->shipping_line_id)
                    ->where('cypa_id', 0) // All CYPA
                    ->where('container_size', $waybill['container_size'])
                    ->where('is_active', 1)
                    ->first();
                if ($fallbackRate) {
                    $totalRatePerClient = $fallbackRate->rate ?? 0.00;
                    // Also set the rate_per_client_id for future reference
                    $ratePerClientId = $fallbackRate->id;
                }
            }

            // Set calculated values
            $waybill['fixed_expense_id'] = $fixedExpenseId;
            $waybill['rate_per_client_id'] = $ratePerClientId; // Can be null, but we try to find a match
            $waybill['total_expense'] = $totalExpense;
            $waybill['total_rate_per_client'] = $totalRatePerClient; // Now should have realistic values

            // Create waybill detail with auto-calculated values
            WaybillDetail::updateOrCreate(
                ['waybill_number' => $waybill['waybill_number']],
                $waybill
            );
        }
    }
}

