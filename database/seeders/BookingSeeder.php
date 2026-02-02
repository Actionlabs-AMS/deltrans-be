<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Bookings link shipping_line → cypa_from → cypa_to. Vessels match shipping line.
     * Waybill details and containers reference booking_id.
     */
    public function run(): void
    {
        $shippingLineIds = DB::table('shipping_lines')->pluck('id')->toArray();
        $shippingLineNames = DB::table('shipping_lines')->pluck('name', 'id')->toArray();
        $cypaIds = DB::table('cypa_details')->where('is_active', 1)->pluck('id')->toArray();

        if (empty($shippingLineIds) || empty($cypaIds) || count($cypaIds) < 2) {
            $this->command->warn('Required related records not found. Please seed shipping_lines and cypa_details first.');
            return;
        }

        $vesselsByLine = [
            'Maersk Line' => ['MAERSK EINDHOVEN', 'MAERSK SEALAND', 'MAERSK CAIRO'],
            'MSC Mediterranean Shipping Company' => ['MSC OSCAR', 'MSC GULSUN', 'MSC MINNA'],
            'CMA CGM' => ['CMA CGM ANTOINE DE SAINT EXUPERY', 'CMA CGM JEAN MERMOZ', 'COSCO SHIPPING'],
            'COSCO Shipping Lines' => ['COSCO SHIPPING NEBULA', 'COSCO SHIPPING UNIVERSE', 'COSCO FAITH'],
            'Evergreen Line' => ['EVER GIVEN', 'EVER GOLDEN', 'EVER GREEN'],
            'Hapag-Lloyd' => ['HAPAG LLOYD EXPRESS', 'HMM OSLO', 'HMM NURI'],
            'ONE (Ocean Network Express)' => ['ONE STORK', 'ONE COLUMBUS', 'ONE STORK'],
            'Yang Ming Marine Transport' => ['YM WIND', 'YM WINDOW', 'YM WELFARE'],
        ];

        $bookings = [];
        $refCounter = 1001;
        $year = now()->format('y');

        foreach ($shippingLineIds as $idx => $lineId) {
            $name = $shippingLineNames[$lineId] ?? 'Maersk Line';
            $vessels = $vesselsByLine[$name] ?? ['VESSEL-' . ($idx + 1)];
            $prefix = strtoupper(substr(preg_replace('/[^A-Z]/', '', $name), 0, 3)) ?: 'BKG';

            foreach ([0, 1] as $v) {
                $cypaFrom = $cypaIds[$idx % count($cypaIds)];
                $cypaTo = $cypaIds[($idx + 1) % count($cypaIds)];
                if ($cypaFrom === $cypaTo) {
                    $cypaTo = $cypaIds[($idx + 2) % count($cypaIds)];
                }
                $bookings[] = [
                    'reference_number' => $prefix . '-' . $year . '-' . $refCounter,
                    'vessel' => $vessels[$v % count($vessels)],
                    'shipping_line_id' => $lineId,
                    'cypa_id_from' => $cypaFrom,
                    'cypa_id_to' => $cypaTo,
                    'expected_date' => now()->addDays(7 + $idx * 3 + $v * 2)->toDateString(),
                    'is_complete' => ($idx + $v) % 3 !== 0,
                ];
                $refCounter++;
            }
        }

        foreach ($bookings as $booking) {
            DB::table('bookings')->updateOrInsert(
                ['reference_number' => $booking['reference_number']],
                array_merge($booking, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
