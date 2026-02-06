<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingStatementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates billing statements only for bookings that have waybills (same relationship as SOA).
     * No fallback: a billing statement is never created for a booking with zero waybills.
     * For the same shipping_line_id + booking_id, SOA and Billing PDF totals will match (same waybills, same formula).
     */
    public function run(): void
    {
        $userId = DB::table('users')->value('id');
        if (!$userId) {
            $this->command->warn('Required related records not found. Please seed users first.');
            return;
        }

        // Only bookings that have at least one waybill (same as StatementOfAccountSeeder)
        $bookingsWithWaybills = DB::table('bookings')
            ->join('waybill_details', 'bookings.id', '=', 'waybill_details.booking_id')
            ->whereNull('waybill_details.deleted_at')
            ->select('bookings.id', 'bookings.shipping_line_id')
            ->distinct()
            ->orderBy('bookings.id')
            ->get();

        if ($bookingsWithWaybills->isEmpty()) {
            $this->command->warn('No bookings with waybills found. Please seed waybill_details first.');
            return;
        }

        // Resolve SOA ids by (shipping_line_id, booking_id) — BillingStatementSeeder runs after StatementOfAccountSeeder
        $soaByBooking = DB::table('statement_of_accounts')
            ->whereNull('deleted_at')
            ->get()
            ->keyBy(fn ($soa) => $soa->shipping_line_id . '-' . $soa->booking_id);

        $statements = [];
        $bsCounter = 1;
        $maxStatements = min(8, $bookingsWithWaybills->count());
        $take = $bookingsWithWaybills->take($maxStatements);

        foreach ($take as $i => $booking) {
            $soaKey = $booking->shipping_line_id . '-' . $booking->id;
            $soaId = $soaByBooking->get($soaKey)?->id;
            if (!$soaId) {
                $this->command->warn("No SOA found for shipping_line_id={$booking->shipping_line_id}, booking_id={$booking->id}. Skipping billing statement.");
                continue;
            }

            $statements[] = [
                'statement_of_account_id' => $soaId,
                'prepared_by' => $userId,
                'billing_statement_no' => 'BS-' . now()->format('Y') . '-' . str_pad((string) $bsCounter, 4, '0', STR_PAD_LEFT),
                'payment_term' => $i % 2 === 0 ? 'Net 30' : 'Net 15',
                'ci_date' => now()->subDays($i + 5)->toDateString(),
                'due_date' => now()->addDays($i * 10 + 15)->toDateString(),
                'bus_style' => $i % 2 === 0 ? 'FCL' : 'LCL',
                'has_details' => $i % 2 === 0 ? 1 : 0, // alternate: true = itemized rows, false = single-row template
                'is_paid' => $i < 2,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $bsCounter++;
        }

        foreach ($statements as $statement) {
            DB::table('billing_statements')->updateOrInsert(
                ['billing_statement_no' => $statement['billing_statement_no']],
                $statement
            );
        }

        $this->command->info('Billing statements seeded successfully. Created ' . count($statements) . ' records (bookings with waybills only).');
    }
}
