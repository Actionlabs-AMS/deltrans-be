<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StatementOfAccount;
use Illuminate\Support\Facades\DB;

class StatementOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates SOAs only for bookings that have at least one waybill (relationship: booking -> waybill_details).
     * No fallback: no SOA is created for a booking without waybills.
     */
    public function run(): void
    {
        // Get available IDs from shipping lines
        $shippingLineIds = DB::table('shipping_lines')
            ->pluck('id')
            ->toArray();

        // Get bookings that have waybills (required)
        $bookingsWithWaybills = DB::table('bookings')
            ->join('waybill_details', 'bookings.id', '=', 'waybill_details.booking_id')
            ->select('bookings.id', 'bookings.shipping_line_id')
            ->distinct()
            ->get();

        if (empty($shippingLineIds)) {
            $this->command->warn('Required shipping lines not found. Please seed shipping_lines first.');
            return;
        }

        if ($bookingsWithWaybills->isEmpty()) {
            $this->command->warn('No bookings with waybills found. Please seed bookings and waybill_details first.');
            return;
        }

        $statementOfAccounts = [];
        $saCounter = 1;
        $bookingIndex = 0;

        // Create SOAs for bookings that have waybills
        foreach ($bookingsWithWaybills as $booking) {
            if ($bookingIndex >= 8) {
                break; // Limit to 8 SOAs
            }

            $statementOfAccounts[] = [
                'shipping_line_id' => $booking->shipping_line_id,
                'dli_sa_number' => 'SA-' . now()->format('Y') . '-' . str_pad((string) $saCounter, 4, '0', STR_PAD_LEFT),
                'booking_id' => $booking->id,
                'work_order' => 'WO-' . now()->format('ym') . '-' . str_pad((string) $saCounter, 3, '0', STR_PAD_LEFT),
            ];

            $saCounter++;
            $bookingIndex++;
        }

        // If we have less than 8 bookings with waybills, create additional SOAs using available bookings
        if (count($statementOfAccounts) < 8) {
            $remainingNeeded = 8 - count($statementOfAccounts);
            $usedBookingIds = collect($statementOfAccounts)->pluck('booking_id')->toArray();

            $additionalBookings = DB::table('bookings')
                ->join('waybill_details', 'bookings.id', '=', 'waybill_details.booking_id')
                ->select('bookings.id', 'bookings.shipping_line_id')
                ->whereNotIn('bookings.id', $usedBookingIds)
                ->distinct()
                ->limit($remainingNeeded)
                ->get();

            foreach ($additionalBookings as $booking) {
                $statementOfAccounts[] = [
                    'shipping_line_id' => $booking->shipping_line_id,
                    'dli_sa_number' => 'SA-' . now()->format('Y') . '-' . str_pad((string) $saCounter, 4, '0', STR_PAD_LEFT),
                    'booking_id' => $booking->id,
                    'work_order' => 'WO-' . now()->format('ym') . '-' . str_pad((string) $saCounter, 3, '0', STR_PAD_LEFT),
                ];
                $saCounter++;
            }
        }

        foreach ($statementOfAccounts as $soa) {
            StatementOfAccount::updateOrCreate(
                ['dli_sa_number' => $soa['dli_sa_number']],
                $soa
            );
        }

        $this->command->info('Statement of accounts seeded successfully. Created ' . count($statementOfAccounts) . ' SOAs.');
    }
}
