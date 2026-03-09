<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListSoaWithNoVat extends Command
{
    protected $signature = 'soa:list-no-vat';

    protected $description = 'List SOA IDs that have at least one waybill with has_vat = false (stored on waybill_details)';

    public function handle(): int
    {
        $this->info('Listing SOAs that have at least one waybill with has_vat = 0/false...');
        $this->newLine();

        $soaIds = DB::table('statement_of_accounts')
            // MariaDB-safe: avoid CAST(... AS JSON); JSON_CONTAINS accepts JSON text like "6"
            ->join('waybill_details', DB::raw('JSON_CONTAINS(statement_of_accounts.booking_ids, CAST(waybill_details.booking_id AS CHAR), \'$\')'), '=', DB::raw('1'))
            ->where('waybill_details.has_vat', 0)
            ->whereNull('statement_of_accounts.deleted_at')
            ->whereNull('waybill_details.deleted_at')
            ->distinct()
            ->pluck('statement_of_accounts.id');

        if ($soaIds->isEmpty()) {
            $this->warn('No SOA found that has a waybill with has_vat = false.');
            $this->line('To get an SOA with no-VAT: create a waybill with has_vat = false (or use a rate per client with has_vat = false when creating the waybill), then generate an SOA for that booking.');
            return 0;
        }

        $this->info('SOA IDs that have at least one no-VAT waybill: ' . $soaIds->join(', '));
        $this->line('Run: php artisan soa:verify-computation <id> to see computation for that SOA.');
        return 0;
    }
}
