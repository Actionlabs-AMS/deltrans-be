<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\StatementOfAccount;
use App\Models\WaybillDetail;
use App\Models\Container;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates invoices for existing SOAs (after StatementOfAccountSeeder).
     * Uses InvoiceService to calculate realistic invoice data from SOA's waybills and containers.
     */
    public function run(): void
    {
        $soas = StatementOfAccount::with('shippingLine')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        if ($soas->isEmpty()) {
            $this->command->warn('No statement of accounts found. Please seed StatementOfAccountSeeder first.');
            return;
        }

        $invoiceService = app(InvoiceService::class);
        $invoices = [];
        $invoiceCounter = 1;

        foreach ($soas as $i => $soa) {
            $bookingIds = $soa->booking_ids ?? [];
            if (empty($bookingIds)) {
                continue;
            }

            // Check if waybills exist for this SOA
            $waybillCount = WaybillDetail::whereIn('booking_id', $bookingIds)
                ->whereNull('deleted_at')
                ->count();

            if ($waybillCount === 0) {
                $this->command->warn("SOA id={$soa->id} has no waybills. Skipping invoice.");
                continue;
            }

            // Calculate invoice data using InvoiceService
            try {
                $invoiceData = $invoiceService->calculateInvoiceFromSoa($soa->id);
            } catch (\Exception $e) {
                $this->command->warn("Failed to calculate invoice for SOA id={$soa->id}: " . $e->getMessage());
                continue;
            }

            // Generate invoice number
            $invoiceNumber = 'INV-' . str_pad((string) $invoiceCounter, 4, '0', STR_PAD_LEFT);

            // Set invoice date (varied dates for realism)
            $invoiceDate = now()->subDays($i * 3 + 2)->toDateString();

            // Add some variation: some invoices have discounts
            $hasDiscount = $i % 3 === 0;
            $discount = $hasDiscount ? round($invoiceData['vatable_sales'] * 0.05, 2) : 0; // 5% discount for some

            // Adjust total_amount if discount applied
            $totalAmount = $invoiceData['total_amount'];
            if ($discount > 0) {
                $totalAmount = max(0, $totalAmount - $discount);
            }

            $invoices[] = [
                'statement_of_account_id' => $soa->id,
                'invoice_number' => $invoiceNumber,
                'date' => $invoiceDate,
                'quantity' => $invoiceData['quantity'],
                'unit_price' => $invoiceData['unit_price'],
                'item_description' => $invoiceData['item_description'],
                'vatable_sales' => $invoiceData['vatable_sales'],
                'zero_rated_sales' => $invoiceData['zero_rated_sales'],
                'vat_exempt_sales' => $invoiceData['vat_exempt_sales'],
                'vat' => $invoiceData['vat'],
                'total_sales' => $invoiceData['total_sales'],
                'less_vat' => $invoiceData['less_vat'],
                'net_of_vat' => $invoiceData['net_of_vat'],
                'discount' => $discount,
                'discount_id' => null, // Can be linked to discounts table if exists
                'less_withdrawing_tax' => $invoiceData['less_withdrawing_tax'],
                'total_amount' => $totalAmount,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $invoiceCounter++;
        }

        foreach ($invoices as $invoice) {
            Invoice::updateOrCreate(
                ['invoice_number' => $invoice['invoice_number']],
                $invoice
            );
        }

        $this->command->info('Invoices seeded successfully. Created ' . count($invoices) . ' invoices (one per SOA).');
    }
}
