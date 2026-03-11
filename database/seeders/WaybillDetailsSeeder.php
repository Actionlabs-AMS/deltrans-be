<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\WaybillDetail;
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
    //                 'no_of_days' => $noOfDays,
    //                 'requirements' => $ratePerClient->requirements ?? null,
    //                 'remarks' => $ratePerClient->remarks ?? null,
    //                 'stack_run' => (float) ($ratePerClient->stack_run ?? 0),
    //                 'rate' => $rate,
    //                 'tax_percent' => $taxPercent,
    //                 'has_vat' => $hasVat,
    //                 'total_rate_per_client' => $totalRatePerClient,
    //                 'total_expense' => ($postExpense) + ($fixedExpense->total_expenses ?? 0),
    //             ];
    //             WaybillDetail::updateOrCreate(
    //                 ['waybill_number' => $waybill['waybill_number']],
    //                 $waybill
    //             );
    //             $waybillNumber++;
    //             $created++;
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
        $trucks = [
            'NAA 1123', 'NAB 2456', 'NAC 3789', 'NAD 4012', 
            'NAE 5234', 'NAF 6567', 'NAG 7890', 'NAH 8123', 
            'NAI 9345', 'NAJ 0456', 'NAK 1567', 'NAL 2678', 
            'NAM 3789', 'NAN 4890'
        ];

        // Define the date range: Feb 23 to March 8, 2026
        $startDate = Carbon::create(2026, 2, 23);
        $endDate = Carbon::create(2026, 3, 8);

        $data = [];

        // Loop through each day in the range
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            
            foreach ($trucks as $plate) {
                // Requirement: 2-3 transactions per day per truck
                $transactionsPerDay = rand(2, 3);

                for ($i = 0; $i < $transactionsPerDay; $i++) {
                    $rate = rand(5000, 15000);
                    $taxPercent = 12; // Standard 12% VAT example
                    $totalRate = $rate * (1 + ($taxPercent / 100));

                    $data[] = [
                        'waybill_number'        => 'WB-' . strtoupper(Str::random(8)),
                        'transaction_date'      => $date->toDateString(),
                        'shipping_line_id'      => rand(1, 5),
                        'booking_id'            => rand(1, 16), // Your foreign key range
                        'driver_id'             => rand(1, 10),
                        'helper_id'             => rand(1, 10),
                        'container_size'        => collect(['20ft', '40ft'])->random(),
                        'container_type'        => collect(['Dry', 'Reefer', 'Flat Rack'])->random(),
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
                        'fixed_expense_id'      => rand(1, 3),
                        'post_expense_amount'   => 500,
                        'total_expense'         => 1500,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];
                }
            }
        }

        // Chunk the insert to avoid memory issues if the dataset is large
        foreach (array_chunk($data, 100) as $chunk) {
            DB::table('waybill_details')->insert($chunk);
        }
    }
}

