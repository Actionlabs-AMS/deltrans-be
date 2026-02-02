<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingStatementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookingIds = DB::table('bookings')->pluck('id')->toArray();
        $shippingLines = DB::table('bookings')
            ->select('id', 'shipping_line_id')
            ->get()
            ->keyBy('id');
        $userId = DB::table('users')->value('id');

        if (empty($bookingIds) || !$userId) {
            $this->command->warn('Required related records not found. Please seed bookings and users first.');
            return;
        }

        $statements = [];
        $bsCounter = 1;
        $maxStatements = min(6, count($bookingIds));

        for ($i = 0; $i < $maxStatements; $i++) {
            $bookingId = $bookingIds[$i];
            $booking = $shippingLines[$bookingId] ?? null;
            if (!$booking) {
                continue;
            }

            $statements[] = [
                'shipping_line_id' => $booking->shipping_line_id,
                'booking_id' => $bookingId,
                'prepared_by' => $userId,
                'billing_statement_no' => 'BS-' . now()->format('Y') . '-' . str_pad((string) $bsCounter, 4, '0', STR_PAD_LEFT),
                'payment_term' => $i % 2 === 0 ? 'Net 30' : 'Net 15',
                'ci_date' => now()->subDays($i + 5)->toDateString(),
                'due_date' => now()->addDays($i * 10 + 15)->toDateString(),
                'bus_style' => $i % 2 === 0 ? 'FCL' : 'LCL',
                'has_details' => $i < 3,
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

        $this->command->info('Billing statements seeded successfully. Created ' . count($statements) . ' records.');
    }
}
