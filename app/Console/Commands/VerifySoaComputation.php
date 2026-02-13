<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StatementOfAccount;
use App\Models\RatePerClient;

class VerifySoaComputation extends Command
{
    protected $signature = 'soa:verify-computation {id=1 : SOA ID to verify}';

    protected $description = 'Verify SOA VAT computation and show DB data for waybills (rate per client data stored on waybill)';

    public function handle(): int
    {
        $id = (int) $this->argument('id');

        $this->info("Loading SOA id={$id} with waybills (from booking_ids)...");

        $soa = StatementOfAccount::with('shippingLine')->find($id);

        if (!$soa) {
            $this->error("SOA #{$id} not found.");
            return 1;
        }

        $waybills = $soa->waybills()->with('booking')->get();
        if ($waybills->isEmpty()) {
            $this->warn("SOA #{$id} has no waybills.");
            return 0;
        }

        $this->table(
            ['Waybill ID', 'rate', 'total_rate_per_client', 'has_vat'],
            $waybills->map(function ($w) {
                return [
                    $w->id,
                    $w->rate ?? '0',
                    $w->total_rate_per_client ?? '0',
                    isset($w->has_vat) ? ($w->has_vat ? 'true' : 'false') : 'null',
                ];
            })->toArray()
        );

        // Run same computation as SoaAndBillingService::generatePdf (using waybill stored fields)
        $totalAmount = 0;
        $totalVat = 0;
        $vatPercent = 12.00;

        foreach ($waybills as $waybill) {
            $amount = (float) ($waybill->rate ?? $waybill->total_rate_per_client ?? 0);
            $waybillHasVat = (bool) ($waybill->has_vat ?? false);
            if ($amount == 0 && $waybill->booking) {
                $matchingRate = RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                    ->where('container_size', $waybill->container_size)
                    ->where(fn($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                    ->where('is_active', 1)
                    ->first();
                if ($matchingRate) {
                    $amount = (float) ($matchingRate->rate ?? 0);
                    $waybillHasVat = (bool) ($matchingRate->has_vat ?? false);
                }
            }

            // waybill.rate is per container, so multiply by container count
            $containerCount = \App\Models\Container::where('booking_id', $waybill->booking_id)
                ->where('waybill_id', $waybill->id)
                ->count();
            if ($containerCount == 0) {
                $containerCount = \App\Models\Container::where('booking_id', $waybill->booking_id)->count();
                if ($containerCount == 0)
                    $containerCount = 1;
            }
            $waybillTotalAmount = $amount * $containerCount;

            $totalAmount += $waybillTotalAmount;
            if ($waybillHasVat) {
                $totalVat += $waybillTotalAmount * ($vatPercent / 100);
            }
        }

        $grandTotal = $totalAmount + $totalVat;

        $this->newLine();
        $this->info('Computation (same as SOA PDF):');
        $this->line("  SUBTOTAL (sum of amounts):  " . number_format($totalAmount, 2, '.', ','));
        $this->line("  VAT (12% where has_vat):    " . number_format($totalVat, 2, '.', ','));
        $this->line("  TOTAL:                     " . number_format($grandTotal, 2, '.', ','));

        $expectedSubtotal = 13500.00;
        $expectedVat = 1620.00;
        $expectedTotal = 15120.00;
        $ok = (abs($totalAmount - $expectedSubtotal) < 0.01 && abs($totalVat - $expectedVat) < 0.01 && abs($grandTotal - $expectedTotal) < 0.01);
        if ($id === 1 && $waybills->count() >= 2) {
            $this->newLine();
            if ($ok) {
                $this->info('Matches expected (SUBTOTAL 13,500 + VAT 1,620 = TOTAL 15,120).');
            } else {
                $this->warn("If this is the sample SOA, expected SUBTOTAL 13,500, VAT 1,620, TOTAL 15,120.");
            }
        }

        return 0;
    }
}
