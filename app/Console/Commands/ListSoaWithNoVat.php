<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListSoaWithNoVat extends Command
{
    protected $signature = 'soa:list-no-vat';

    protected $description = 'List SOA IDs that have at least one waybill with rate_per_client.has_vat = false';

    public function handle(): int
    {
        $this->info('has_vat is on rate_per_client, not on SOA. Each waybill links to a rate_per_client.');
        $this->info('Listing SOAs that have at least one waybill whose rate_per_client has has_vat = 0/false...');
        $this->newLine();

        $soaIds = DB::table('statement_of_accounts')
            ->join('waybill_details', 'waybill_details.booking_id', '=', 'statement_of_accounts.booking_id')
            ->join('rate_per_clients', 'rate_per_clients.id', '=', 'waybill_details.rate_per_client_id')
            ->where('rate_per_clients.has_vat', 0)
            ->whereNull('statement_of_accounts.deleted_at')
            ->distinct()
            ->pluck('statement_of_accounts.id');

        if ($soaIds->isEmpty()) {
            $this->warn('No SOA found that has a waybill with has_vat = false.');
            $this->line('To get an SOA with no-VAT: create a waybill that uses a rate_per_client with has_vat = false (e.g. the seeded "No VAT (sample)" rates), then generate an SOA for that booking.');
            return 0;
        }

        $this->info('SOA IDs that have at least one no-VAT waybill: ' . $soaIds->join(', '));
        $this->line('Run: php artisan soa:verify-computation <id> to see computation for that SOA.');
        return 0;
    }
}
