<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StatementOfAccount;
use App\Models\RatePerClient;

class VerifySoaComputation extends Command
{
    protected $signature = 'soa:verify-computation {id=1 : SOA ID to verify}';

    protected $description = 'Verify SOA VAT computation and show DB data for waybills and rate_per_clients';

    public function handle(): int
    {
        $id = (int) $this->argument('id');

        $this->info("Loading SOA id={$id} with booking.waybills and ratePerClient...");

        $soa = StatementOfAccount::with([
            'shippingLine',
            'booking.waybills' => function ($query) {
                $query->with(['ratePerClient']);
            }
        ])->find($id);

        if (!$soa) {
            $this->error("SOA #{$id} not found.");
            return 1;
        }

        $waybills = $soa->booking->waybills ?? collect();
        if ($waybills->isEmpty()) {
            $this->warn("SOA #{$id} has no waybills.");
            return 0;
        }

        $this->table(
            ['Waybill ID', 'total_rate_per_client', 'rate_per_client_id', 'RPC rate', 'RPC has_vat'],
            $waybills->map(function ($w) {
                $rpc = $w->ratePerClient;
                return [
                    $w->id,
                    $w->total_rate_per_client ?? '0',
                    $w->rate_per_client_id ?? 'null',
                    $rpc ? ($rpc->rate ?? '') : '-',
                    $rpc ? (isset($rpc->has_vat) ? ($rpc->has_vat ? 'true' : 'false') : 'null') : '-',
                ];
            })->toArray()
        );

        // Raw DB check: rate_per_clients for these IDs
        $rpcIds = $waybills->pluck('rate_per_client_id')->filter()->unique();
        if ($rpcIds->isNotEmpty()) {
            $this->info('Rate per clients (from DB):');
            $rows = RatePerClient::whereIn('id', $rpcIds)->get(['id', 'rate', 'has_vat', 'container_size']);
            $this->table(['id', 'rate', 'has_vat', 'container_size'], $rows->map(fn ($r) => [
                $r->id,
                $r->rate,
                isset($r->has_vat) ? ($r->has_vat ? '1' : '0') : 'null',
                $r->container_size,
            ])->toArray());
        }

        // Run same computation as SoaAndBillingService::generatePdf
        $totalAmount = 0;
        $totalVat = 0;
        $vatPercent = 12.00;

        foreach ($waybills as $waybill) {
            $amount = $waybill->total_rate_per_client ?? 0;
            $waybillHasVat = false;

            if ($waybill->ratePerClient) {
                $rpc = $waybill->ratePerClient;
                if ($amount == 0) {
                    $amount = $rpc->rate ?? 0;
                }
                $waybillHasVat = $rpc->has_vat ?? false;
            }
            if ($amount == 0 && $waybill->booking) {
                $matchingRate = RatePerClient::where('shipping_line_id', $waybill->shipping_line_id)
                    ->where('container_size', $waybill->container_size)
                    ->where(fn ($q) => $q->where('cypa_id', $waybill->booking->cypa_id_from)->orWhere('cypa_id', 0))
                    ->where('is_active', 1)
                    ->first();
                if ($matchingRate) {
                    if ($amount == 0) {
                        $amount = $matchingRate->rate ?? 0;
                    }
                    $waybillHasVat = $matchingRate->has_vat ?? false;
                }
            }

            $totalAmount += $amount;
            if ($waybillHasVat) {
                $totalVat += $amount * ($vatPercent / 100);
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
