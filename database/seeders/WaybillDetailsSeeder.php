<?php

namespace Database\Seeders;

use App\Models\DieselExpense;
use App\Models\WaybillDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class WaybillDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Uses only proper relationships: fixed_expense by (booking.shipping_line_id, booking.cypa_id_from, booking.cypa_id_to, container_size),
     * rate_per_client by (booking.shipping_line_id, booking.cypa_id_from, container_size) then cypa_id=0.
     * No fallback: waybills are created only when both fixed_expense and rate_per_client exist; otherwise the (booking_id, container_size) is skipped.
     */
    // public function run(): void
    // {
    //     // Get available IDs from related tables
    //     $shippingLineIds = DB::table('shipping_lines')
    //         ->pluck('id')
    //         ->toArray();

    //     $driverIds = DB::table('drivers')
    //         ->where('is_active', 1)
    //         ->pluck('id')
    //         ->toArray();

    //     $helperIds = DB::table('helpers')
    //         ->where('is_active', 1)
    //         ->pluck('id')
    //         ->toArray();

    //     $fleetTruckPlateNumbers = DB::table('fleet_trucks')
    //         ->where('is_active', 1)
    //         ->pluck('plate_number')
    //         ->toArray();

    //     $bookingIds = DB::table('bookings')
    //         ->pluck('id')
    //         ->toArray();

    //     $firstUserId = DB::table('users')->value('id');

    //     $bookings = DB::table('bookings')
    //         ->select('id', 'shipping_line_id', 'cypa_id_from', 'cypa_id_to')
    //         ->get()
    //         ->keyBy('id');

    //     $driversWithHelperId = DB::table('drivers')
    //         ->select('id', 'helper_id')
    //         ->get()
    //         ->keyBy('id');

    //     if (empty($shippingLineIds) || empty($driverIds) || empty($fleetTruckPlateNumbers) || empty($bookingIds)) {
    //         $this->command->warn('Required related records not found. Please seed shipping_lines, drivers, fleet_trucks, bookings, and rate_per_clients first.');
    //         return;
    //     }

    //     // Find fixed_expense_id by (shipping_line_id, cypa_id_from, cypa_id_to, container_size)
    //     $findFixedExpenseId = function ($bookingId, $containerSize) use ($bookings) {
    //         if (!isset($bookings[$bookingId])) {
    //             return null;
    //         }
    //         $b = $bookings[$bookingId];
    //         $row = DB::table('fixed_expenses')
    //             ->where('shipping_line_id', $b->shipping_line_id)
    //             ->where('cypa_id_from', $b->cypa_id_from)
    //             ->where('cypa_id_to', $b->cypa_id_to)
    //             ->where('container_size', $containerSize)
    //             ->first();
    //         return $row ? $row->id : null;
    //     };

    //     // Find rate_per_client row by (shipping_line_id, cypa_id_from, container_size); prefer specific cypa then cypa_id=0 (for redundant copy into waybill_details)
    //     $findRatePerClient = function ($bookingId, $containerSize) use ($bookings) {
    //         if (!isset($bookings[$bookingId])) {
    //             return null;
    //         }
    //         $b = $bookings[$bookingId];
    //         $row = DB::table('rate_per_clients')
    //             ->where('shipping_line_id', $b->shipping_line_id)
    //             ->where('cypa_id', $b->cypa_id_from)
    //             ->where('container_size', $containerSize)
    //             ->where('is_active', 1)
    //             ->first();
    //         if (!$row) {
    //             $row = DB::table('rate_per_clients')
    //                 ->where('shipping_line_id', $b->shipping_line_id)
    //                 ->where('cypa_id', 0)
    //                 ->where('container_size', $containerSize)
    //                 ->where('is_active', 1)
    //                 ->first();
    //         }
    //         return $row;
    //     };

    //     // Build waybills only for (booking_id, container_size) that have both fixed_expense and rate_per_client (no fallbacks)
    //     $waybillNumber = 1;
    //     $maxWaybills = 15;
    //     $containerSizes = ['20ft', '40ft'];
    //     $containerTypes = ['DRY', 'DRY', 'REEFER'];
    //     $created = 0;

    //     foreach ($bookingIds as $bi => $bookingId) {
    //         if ($created >= $maxWaybills) {
    //             break;
    //         }
    //         foreach ($containerSizes as $csIdx => $containerSize) {
    //             if ($created >= $maxWaybills) {
    //                 break;
    //             }
    //             $fixedExpenseId = $findFixedExpenseId($bookingId, $containerSize);
    //             $ratePerClient = $findRatePerClient($bookingId, $containerSize);
    //             if ($fixedExpenseId === null || $ratePerClient === null) {
    //                 continue;
    //             }
    //             $booking = $bookings[$bookingId];
    //             $driverIdx = $created % count($driverIds);
    //             $truckIdx = $created % count($fleetTruckPlateNumbers);
    //             $driver = $driversWithHelperId[$driverIds[$driverIdx]] ?? null;
    //             $helperId = ($driver && $driver->helper_id) ? $driver->helper_id : null;
    //             $fixedExpense = DB::table('fixed_expenses')->find($fixedExpenseId);
    //             $rate = (float) ($ratePerClient->rate ?? 0);
    //             $taxPercent = $ratePerClient->tax_percent !== null ? (float) $ratePerClient->tax_percent : null;
    //             $hasVat = (bool) ($ratePerClient->has_vat ?? true);
    //             $totalRatePerClient = $rate; // store base rate; SOA/billing can apply VAT from has_vat/tax_percent
    //             $noOfDays = (int) ($ratePerClient->no_of_days ?? 0);
    //             $postExpense = ($created % 3 === 0) ? 0.00 : (($created % 3 === 1) ? 200.00 : 300.00);
    //             $daysAgo = 2 + ($created % 5);
    //             $waybill = [
    //                 'waybill_number' => 'WB-' . str_pad((string) $waybillNumber, 3, '0', STR_PAD_LEFT),
    //                 'transaction_date' => now()->subDays($daysAgo)->toDateString(),
    //                 'shipping_line_id' => $booking->shipping_line_id,
    //                 'booking_id' => $bookingId,
    //                 'driver_id' => $driverIds[$driverIdx],
    //                 'helper_id' => $helperId,
    //                 'container_size' => $containerSize,
    //                 'container_type' => $containerTypes[$created % count($containerTypes)],
    //                 'truck_plate_number' => $fleetTruckPlateNumbers[$truckIdx],
    //                 'pickup_date' => now()->subDays($daysAgo)->toDateString(),
    //                 'delivered_date' => now()->subDays($daysAgo - 1)->toDateString(),
    //                 'post_expense_amount' => $postExpense,
    //                 'fixed_expense_id' => $fixedExpenseId,
    //                 'truck_trip_expense_id' => null,
    //                 'diesel_expense_id' => null,
    //                 'no_of_days' => $noOfDays,
    //                 'requirements' => $ratePerClient->requirements ?? null,
    //                 'remarks' => $ratePerClient->remarks ?? null,
    //                 'stack_run' => (float) ($ratePerClient->stack_run ?? 0),
    //                 'rate' => $rate,
    //                 'tax_percent' => $taxPercent,
    //                 'has_vat' => $hasVat,
    //                 'total_rate_per_client' => $totalRatePerClient,
    //                 'actual_truck_trip_expense_amount' => 0,
    //                 'total_expense' => ($postExpense) + ($fixedExpense->total_expenses ?? 0),
    //             ];
    //             if ($firstUserId !== null) {
    //                 $waybill['prepared_by'] = $firstUserId;
    //             }
    //             WaybillDetail::updateOrCreate(
    //                 ['waybill_number' => $waybill['waybill_number']],
    //                 $waybill
    //             );
    //             $waybillNumber++;
    //             $created++;
    //         }
    //     }

    //     // Link first 2 waybills to first 2 diesel expenses (so seed data includes purchase_order)
    //     $dieselIds = DieselExpense::orderBy('id')->limit(2)->pluck('id')->toArray();
    //     if (count($dieselIds) >= 2) {
    //         $waybillsToLink = WaybillDetail::orderBy('id')->limit(2)->get();
    //         foreach ($waybillsToLink as $i => $wd) {
    //             $dieselId = $dieselIds[$i] ?? null;
    //             if ($dieselId) {
    //                 $diesel = DieselExpense::find($dieselId);
    //                 $wd->update([
    //                     'diesel_expense_id' => $dieselId,
    //                     'total_expense' => (float) $wd->total_expense + (float) ($diesel->amount ?? 0),
    //                 ]);
    //             }
    //         }
    //     }

    //     // Assign no-VAT to first 2 waybills so at least one SOA has has_vat = false (for testing)
    //     $noVatRates = DB::table('rate_per_clients')
    //         ->where('has_vat', 0)
    //         ->where('is_active', 1)
    //         ->get()
    //         ->keyBy('container_size');
    //     if ($noVatRates->isNotEmpty()) {
    //         $waybillsToSwitch = WaybillDetail::orderBy('id')->limit(2)->get();
    //         foreach ($waybillsToSwitch as $wd) {
    //             $noVat = $noVatRates->get($wd->container_size);
    //             if ($noVat) {
    //                 $wd->update([
    //                     'has_vat' => false,
    //                     'rate' => (float) $noVat->rate,
    //                     'total_rate_per_client' => (float) $noVat->rate,
    //                     'tax_percent' => $noVat->tax_percent !== null ? (float) $noVat->tax_percent : null,
    //                     'remarks' => $noVat->remarks ?? $wd->remarks,
    //                     'requirements' => $noVat->requirements ?? $wd->requirements,
    //                     'stack_run' => (float) ($noVat->stack_run ?? 0),
    //                     'no_of_days' => (int) ($noVat->no_of_days ?? 0),
    //                 ]);
    //             }
    //         }
    //     }

    //     $this->command->info("Waybill details seeded: {$created} waybills (rate per client data stored inline).");
    // }
    
    public function run(): void
    {
        $truckPlates = DB::table('fleet_trucks')->pluck('plate_number')->toArray();
        $bookingRows = DB::table('bookings')->select('id', 'shipping_line_id')->get();
        $bookingIds = $bookingRows->pluck('id')->toArray();
        $bookingToShippingLine = $bookingRows->pluck('shipping_line_id', 'id')->toArray();
        $drivers = DB::table('drivers')
            ->select('id', 'helper_id', 'assigned_truck_plate_numbers', 'is_active')
            ->get();
        $activeDrivers = $drivers->where('is_active', 1)->values();
        $activeDriverIds = $activeDrivers->pluck('id')->toArray();
        $driverHelperById = $drivers->pluck('helper_id', 'id')->toArray();

        $helpers = DB::table('helpers')->select('id', 'is_active')->get();
        $activeHelperIds = $helpers->where('is_active', 1)->pluck('id')->toArray();
        $fixedExpenseIds = DB::table('fixed_expenses')->pluck('id')->toArray();
        $truckTripExpenseIds = DB::table('truck_trip_expense')->pluck('id')->toArray();
        $dieselExpenseIds = DB::table('diesel_expenses')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();

        if (empty($truckPlates) || empty($bookingIds) || empty($activeDriverIds) || empty($fixedExpenseIds)) {
            $this->command?->warn('Required related records not found. Please seed fleet_trucks, bookings, drivers, and fixed_expenses first.');
            return;
        }

        $startDate = Carbon::create(2026, 2, 23);
        $endDate = Carbon::create(2026, 3, 8);
        
        // 1. Calculate approximate total rows first
        // 14 trucks * 14 days * ~2.5 avg trips = ~490 to 500 rows
        $totalExpectedRows = 600; // Over-estimate to be safe

        // 2. Create the "Pool" of diesel IDs + nulls (diesel_expense_id is nullable)
        $dieselSample = !empty($dieselExpenseIds) ? array_slice($dieselExpenseIds, 0, min(100, count($dieselExpenseIds))) : [];
        $dieselPool = array_merge(
            $dieselSample,
            array_fill(0, max(0, $totalExpectedRows - count($dieselSample)), null)
        );
        
        // 3. Shuffle the pool so the 100 IDs are scattered everywhere
        $shuffledPool = collect($dieselPool)->shuffle();

        $data = [];
        $containerSizes = ['20ft', '40ft'];
        $containerTypes = ['Dry', 'Reefer', 'Flat Rack'];

        // Build plate -> driver_ids map using drivers.assigned_truck_plate_numbers (JSON)
        $driverIdsByPlate = [];
        foreach ($activeDrivers as $d) {
            $assigned = $d->assigned_truck_plate_numbers;
            if ($assigned === null || $assigned === '') {
                continue;
            }
            $plates = is_string($assigned) ? json_decode($assigned, true) : $assigned;
            if (!is_array($plates)) {
                continue;
            }
            foreach ($plates as $p) {
                if (!is_string($p) || $p === '') {
                    continue;
                }
                $driverIdsByPlate[$p] ??= [];
                $driverIdsByPlate[$p][] = (int) $d->id;
            }
        }

        // helper_id is nullable; include nulls for realism (prefer active helpers)
        $helperPool = !empty($activeHelperIds)
            ? array_merge($activeHelperIds, array_fill(0, max(1, (int) floor(count($activeHelperIds) / 3)), null))
            : [null];

        // prepared_by is nullable; include nulls too
        $preparedByPool = !empty($userIds) ? array_merge($userIds, array_fill(0, max(1, (int) floor(count($userIds) / 4)), null)) : [null];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            foreach ($truckPlates as $plate) {
                $transactionsPerDay = rand(2, 3);

                for ($i = 0; $i < $transactionsPerDay; $i++) {
                    $bookingId = $bookingIds[array_rand($bookingIds)];
                    $shippingLineId = $bookingToShippingLine[$bookingId] ?? null;
                    if ($shippingLineId === null) {
                        // Should not happen, but skip if booking row is inconsistent
                        continue;
                    }

                    // Prefer a driver assigned to this plate; fallback to any active driver.
                    $candidateDrivers = $driverIdsByPlate[$plate] ?? $activeDriverIds;
                    $driverId = $candidateDrivers[array_rand($candidateDrivers)];

                    // Prefer helper assigned to the driver if present; fallback to helper pool (nullable).
                    $driverHelperId = $driverHelperById[$driverId] ?? null;
                    $helperId = $driverHelperId !== null ? (int) $driverHelperId : $helperPool[array_rand($helperPool)];

                    $rate = rand(5000, 15000);
                    $taxPercent = 12;
                    $totalRate = $rate * (1 + ($taxPercent / 100));

                    $data[] = [
                        'waybill_number'        => 'WB-' . strtoupper(Str::random(8)),
                        'transaction_date'      => $date->toDateString(),
                        'shipping_line_id'      => $shippingLineId,
                        'booking_id'            => $bookingId,
                        'driver_id'             => $driverId,
                        'helper_id'             => $helperId,
                        'container_size'        => $containerSizes[array_rand($containerSizes)],
                        'container_type'        => $containerTypes[array_rand($containerTypes)],
                        'truck_plate_number'    => $plate,
                        'pickup_date'           => $date->copy()->addHours(rand(1, 5)),
                        'delivered_date'        => $date->copy()->addHours(rand(6, 12)),
                        'no_of_days'            => 1,
                        'requirements'          => 'Standard Documents',
                        'remarks'               => 'Seeded Transaction',
                        'stack_run'             => 'SR-' . rand(100, 999),
                        'rate'                  => $rate,
                        'tax_percent'           => $taxPercent,
                        'has_vat'               => true,
                        'total_rate_per_client' => $totalRate,
                        'fixed_expense_id'      => $fixedExpenseIds[array_rand($fixedExpenseIds)],
                        'truck_trip_expense_id' => !empty($truckTripExpenseIds) ? $truckTripExpenseIds[array_rand($truckTripExpenseIds)] : null,
                        
                        // 🌟 4. Pull from the shuffled pool of IDs and Nulls
                        'diesel_expense_id'     => $shuffledPool->shift(), 
                        
                        'post_expense_amount'   => 500,
                        'actual_truck_trip_expense_amount'    => rand(1,100),
                        'total_expense'         => 1500,
                        'prepared_by'           => $preparedByPool[array_rand($preparedByPool)],
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($data, 100) as $chunk) {
            DB::table('waybill_details')->insert($chunk);
        }
    }
}

