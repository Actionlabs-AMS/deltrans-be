<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatePerClientProductionSeeder extends Seeder
{
    public function run(): void
    {
        $shippingLines = DB::table('shipping_lines')
            ->select(['id', 'short_name', 'name'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        // Prefer the lowest ID when duplicates exist.
        $shippingLineIdByShortName = [];
        $shippingLineIdByName = [];
        foreach ($shippingLines as $r) {
            if (is_string($r->short_name) && trim($r->short_name) !== '' && ! isset($shippingLineIdByShortName[$r->short_name])) {
                $shippingLineIdByShortName[$r->short_name] = (int) $r->id;
            }
            if (is_string($r->name) && trim($r->name) !== '' && ! isset($shippingLineIdByName[$r->name])) {
                $shippingLineIdByName[$r->name] = (int) $r->id;
            }
        }

        $cypaIdByShortName = DB::table('cypa_details')
            ->whereNull('deleted_at')
            ->select(['id', 'short_name'])
            ->orderBy('id')
            ->get()
            ->reduce(function (array $carry, $row) {
                $key = is_string($row->short_name) ? trim($row->short_name) : '';
                if ($key !== '' && ! isset($carry[$key])) {
                    $carry[$key] = (int) $row->id;
                }
                return $carry;
            }, []);

        $rows = [
            // ONE
            [
                'client_short_name' => 'ONE',
                'no_of_days' => 45,
                'requirements' => "BILLING STATEMENT/SOA/WOR\nKORDER",
                'remarks' => 'After work order provided by ONE',
                'cypa_short_name' => 'ALL CY',
                'stack_run' => 0,
                'rates' => [
                    '20ft' => 6700,
                    '40ft' => 6400,
                    '20ft(offhire)' => 8000,
                    '40ft(offhire)' => 7500,
                ],
            ],
            // KMTC
            [
                'client_short_name' => 'KMTC',
                'no_of_days' => 15,
                'requirements' => "SOA/REPO\nINSTRUCTION/SCAN\nEIR",
                'remarks' => 'After submission of billing invoice',
                'cypa_short_name' => 'ALL CY',
                'stack_run' => 0,
                'rates' => [
                    '20ft' => 7150,
                    '40ft' => 7150,
                ],
            ],
            // OOCL (multiple CYPA rows)
            [
                'client_short_name' => 'OOCL',
                'no_of_days' => 30,
                'requirements' => "SOA/BILLING\nSTATEMENT/\nSTACKRUN/INVOICE",
                'remarks' => 'After submission of billing invoice',
                'cypa_short_name' => 'TOCSI',
                'stack_run' => 112,
                'rates' => [
                    '20ft' => 6000,
                    '40ft' => 6500,
                ],
            ],
            [
                'client_short_name' => 'OOCL',
                'no_of_days' => 30,
                'requirements' => "SOA/BILLING\nSTATEMENT/\nSTACKRUN/INVOICE",
                'remarks' => 'After submission of billing invoice',
                'cypa_short_name' => 'SEACON',
                'stack_run' => 112,
                'rates' => [
                    '20ft' => 8000,
                    '40ft' => 7000,
                ],
            ],
            // SEA LEAD
            [
                'client_short_name' => 'SEA LEAD',
                'no_of_days' => 15,
                'requirements' => 'SOA',
                'remarks' => 'After submission of billing invoice (STACKRUN)',
                'cypa_short_name' => 'ALL CY',
                'stack_run' => 0,
                'rates' => [
                    '20ft' => 6500,
                    '40ft' => 6500,
                ],
            ],
            // INTERASIA
            [
                'client_short_name' => 'INTERASIA',
                'no_of_days' => 15,
                'requirements' => "SOA/BILLING\nSTATEMENT",
                'remarks' => 'After submission of billing invoice',
                'cypa_short_name' => 'ALL CY',
                'stack_run' => 0,
                'rates' => [
                    '20ft' => 7500,
                    '40ft' => 7500,
                ],
            ],
            // TS LINE
            [
                'client_short_name' => 'TS LINE',
                'no_of_days' => 15,
                'requirements' => "SOA THEN INVOICE",
                'remarks' => 'After submission of billing invoice',
                'cypa_short_name' => 'ALL CY',
                'stack_run' => 0,
                'rates' => [
                    '20ft' => 6700,
                    '40ft' => 6700,
                ],
            ],
            // MEDLOG (with 12% VAT note)
            [
                'client_short_name' => 'MEDLOG',
                'no_of_days' => 30,
                'requirements' => 'SOA/SALES INVOICE',
                'remarks' => 'After submission of billing invoice (with 12% vat)',
                'cypa_short_name' => 'ALL CY',
                'stack_run' => 0,
                'rates' => [
                    '20ft' => 5000,
                    '40ft' => 5000,
                ],
                'tax_percent' => 12,
                'has_vat' => true,
            ],
            // MSC (with 12% VAT note)
            [
                'client_short_name' => 'MSC',
                'no_of_days' => 30,
                'requirements' => 'SOA/SALES INVOICE',
                'remarks' => 'After submission of billing invoice (with 12% vat)',
                'cypa_short_name' => 'ALL CY',
                'stack_run' => 0,
                'rates' => [
                    '20ft' => 6500,
                    '40ft' => 6500,
                ],
                'tax_percent' => 12,
                'has_vat' => true,
            ],
            // HMM / Hyundai
            [
                'client_short_name' => 'HMM',
                'no_of_days' => 30,
                'requirements' => null,
                'remarks' => null,
                'cypa_short_name' => 'OCEANBOX',
                'stack_run' => 0,
                'rates' => [
                    '20ft' => 7300,
                    '40ft' => 7300,
                ],
            ],
        ];

        foreach ($rows as $row) {
            $clientShortName = (string) $row['client_short_name'];
            $shippingLineId = $this->resolveShippingLineId($clientShortName, $shippingLineIdByShortName, $shippingLineIdByName);

            if (! $shippingLineId) {
                $this->command?->warn("Skipping rate_per_clients for '{$clientShortName}': shipping line not found.");
                continue;
            }

            $cypaShort = (string) ($row['cypa_short_name'] ?? 'ALL CY');
            $cypaId = $cypaShort === 'ALL CY' ? 0 : ($cypaIdByShortName[$cypaShort] ?? null);

            if ($cypaId === null) {
                $this->command?->warn("Skipping '{$clientShortName}' rates for CYPA '{$cypaShort}': cypa_details not found.");
                continue;
            }

            $taxPercent = array_key_exists('tax_percent', $row) ? $this->toDecimalOrNull($row['tax_percent']) : 12.00;
            $hasVat = array_key_exists('has_vat', $row) ? (bool) $row['has_vat'] : true;

            foreach (($row['rates'] ?? []) as $containerSize => $rate) {
                $rateDecimal = $this->toDecimalOrNull($rate);
                if ($rateDecimal === null) {
                    continue;
                }

                DB::table('rate_per_clients')->updateOrInsert(
                    [
                        'shipping_line_id' => $shippingLineId,
                        'cypa_id' => $cypaId,
                        'container_size' => (string) $containerSize,
                    ],
                    [
                        'shipping_line_id' => $shippingLineId,
                        'no_of_days' => (int) ($row['no_of_days'] ?? 0),
                        'requirements' => $this->normalizeText($row['requirements'] ?? null),
                        'remarks' => $this->normalizeText($row['remarks'] ?? null),
                        'cypa_id' => $cypaId,
                        'container_size' => (string) $containerSize,
                        'stack_run' => $this->toDecimalOrZero($row['stack_run'] ?? 0),
                        'rate' => $rateDecimal,
                        'tax_percent' => $taxPercent,
                        'has_vat' => $hasVat,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function resolveShippingLineId(string $clientShortName, array $idByShortName, array $idByName): ?int
    {
        if (isset($idByShortName[$clientShortName])) {
            return (int) $idByShortName[$clientShortName];
        }

        $mapToShortName = [
            'HMM' => 'HYUNDAI',
        ];

        $shortNameAlias = $mapToShortName[$clientShortName] ?? null;
        if ($shortNameAlias && isset($idByShortName[$shortNameAlias])) {
            return (int) $idByShortName[$shortNameAlias];
        }

        $mapToName = [
            'ONE' => 'OCEAN NETWORK EXPRESS PTE LTD',
            'KMTC' => 'KOREA MARINE TRANSPORT CO LTD',
            'OOCL' => 'ORIENT OVERSEAS CONTAINER LINE, LTD',
            'INTERASIA' => 'FREIGHT CONNECTION PHIL INC',
            'TS LINE' => 'TS LINES LTD, C/O TSL CONTAINER LINES PHIL INC',
            'SEA LEAD' => 'SEALEAD SHIPPING PTE. LTD.',
            'MEDLOG' => 'MEDLOG PHILIPPINES INC',
            'MSC' => 'MEDITERRANEAN SHIPPING COMPANY PHILIPPINES',
            'HMM' => 'HMM (Philippines), Inc.',
        ];

        $name = $mapToName[$clientShortName] ?? null;
        if ($name && isset($idByName[$name])) {
            return (int) $idByName[$name];
        }

        return null;
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return $text;
    }

    private function toDecimalOrNull(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        $s = str_replace([',', ' '], '', $s);
        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    private function toDecimalOrZero(mixed $value): float
    {
        return $this->toDecimalOrNull($value) ?? 0.00;
    }
}

