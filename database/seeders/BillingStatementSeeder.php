<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingStatementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates one billing statement per SOA (after StatementOfAccountSeeder).
     * SOA and Billing PDF totals match: same waybills from SOA booking_ids, same formula (base + 12% VAT where has_vat).
     */
    public function run(): void
    {
        $userId = DB::table('users')->value('id');
        if (!$userId) {
            $this->command->warn('Required related records not found. Please seed users first.');
            return;
        }

        $soas = DB::table('statement_of_accounts')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        if ($soas->isEmpty()) {
            $this->command->warn('No statement of accounts found. Please seed StatementOfAccountSeeder first.');
            return;
        }

        $statements = [];
        $bsCounter = 1;

        foreach ($soas as $i => $soa) {
            $bookingIds = json_decode($soa->booking_ids ?? '[]', true);
            if (!is_array($bookingIds) || empty($bookingIds)) {
                continue;
            }
            $waybillCount = DB::table('waybill_details')
                ->whereIn('booking_id', $bookingIds)
                ->whereNull('deleted_at')
                ->count();
            if ($waybillCount === 0) {
                $this->command->warn("SOA id={$soa->id} has no waybills. Skipping billing statement.");
                continue;
            }

            $statements[] = [
                'statement_of_account_id' => $soa->id,
                'prepared_by' => $userId,
                'billing_statement_no' => 'BS-' . now()->format('Y') . '-' . str_pad((string) $bsCounter, 4, '0', STR_PAD_LEFT),
                'payment_term' => $i % 2 === 0 ? 'Net 30' : 'Net 15',
                'ci_date' => now()->subDays($i + 5)->toDateString(),
                'due_date' => now()->addDays($i * 10 + 15)->toDateString(),
                'bus_style' => $i % 2 === 0 ? 'FCL' : 'LCL',
                'has_details' => $i % 2 === 0 ? 1 : 0,
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

        $this->command->info('Billing statements seeded successfully. Created ' . count($statements) . ' records (one per SOA).');
    }
}
