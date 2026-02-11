<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StatementOfAccount;
use Illuminate\Support\Facades\DB;

class StatementOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates SOAs for bookings that have at least one waybill.
     * Includes both single-booking and multi-booking SOAs so PDFs can be verified with multiple bookings/waybills.
     */
    public function run(): void
    {
        $shippingLineIds = DB::table('shipping_lines')->pluck('id')->toArray();

        $bookingsWithWaybills = DB::table('bookings')
            ->join('waybill_details', 'bookings.id', '=', 'waybill_details.booking_id')
            ->whereNull('waybill_details.deleted_at')
            ->select('bookings.id', 'bookings.shipping_line_id')
            ->distinct()
            ->orderBy('bookings.shipping_line_id')
            ->orderBy('bookings.id')
            ->get();

        if (empty($shippingLineIds)) {
            $this->command->warn('Required shipping lines not found. Please seed shipping_lines first.');
            return;
        }

        if ($bookingsWithWaybills->isEmpty()) {
            $this->command->warn('No bookings with waybills found. Please seed bookings and waybill_details first.');
            return;
        }

        // Group bookings by shipping_line_id so we can create multi-booking SOAs per line
        $byShippingLine = $bookingsWithWaybills->groupBy('shipping_line_id');

        $statementOfAccounts = [];
        $saCounter = 1;
        $usedBookingIds = [];

        foreach ($byShippingLine as $shippingLineId => $bookings) {
            $bookings = $bookings->values();
            $remaining = $bookings->reject(fn ($b) => in_array($b->id, $usedBookingIds))->values();

            if ($remaining->isEmpty()) {
                continue;
            }

            // First SOA for this shipping line: use 2+ bookings if available (multi-booking SOA for PDF testing)
            $chunk = $remaining->take(2)->pluck('id')->toArray();
            if (!empty($chunk)) {
                $statementOfAccounts[] = [
                    'shipping_line_id' => (int) $shippingLineId,
                    'dli_sa_number' => 'SA-' . now()->format('Y') . '-' . str_pad((string) $saCounter, 4, '0', STR_PAD_LEFT),
                    'booking_ids' => $chunk,
                    'work_order' => 'WO-' . now()->format('ym') . '-' . str_pad((string) $saCounter, 3, '0', STR_PAD_LEFT),
                ];
                $usedBookingIds = array_merge($usedBookingIds, $chunk);
                $saCounter++;
            }

            // Additional SOAs for same line: single or multiple bookings
            $remaining = $bookings->reject(fn ($b) => in_array($b->id, $usedBookingIds))->values();
            while ($remaining->isNotEmpty() && count($statementOfAccounts) < 10) {
                $chunk = $remaining->take(2)->pluck('id')->toArray();
                $remaining = $remaining->slice(count($chunk));
                if (empty($chunk)) {
                    break;
                }
                $statementOfAccounts[] = [
                    'shipping_line_id' => (int) $shippingLineId,
                    'dli_sa_number' => 'SA-' . now()->format('Y') . '-' . str_pad((string) $saCounter, 4, '0', STR_PAD_LEFT),
                    'booking_ids' => $chunk,
                    'work_order' => 'WO-' . now()->format('ym') . '-' . str_pad((string) $saCounter, 3, '0', STR_PAD_LEFT),
                ];
                $usedBookingIds = array_merge($usedBookingIds, $chunk);
                $saCounter++;
            }

            if (count($statementOfAccounts) >= 10) {
                break;
            }
        }

        foreach ($statementOfAccounts as $soa) {
            StatementOfAccount::updateOrCreate(
                ['dli_sa_number' => $soa['dli_sa_number']],
                $soa
            );
        }

        $multiCount = collect($statementOfAccounts)->filter(fn ($s) => count($s['booking_ids']) > 1)->count();
        $this->command->info('Statement of accounts seeded successfully. Created ' . count($statementOfAccounts) . ' SOAs (' . $multiCount . ' with multiple bookings).');
    }
}
