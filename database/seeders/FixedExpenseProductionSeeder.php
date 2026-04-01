<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixedExpenseProductionSeeder extends Seeder
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
            ->select(['id', 'short_name'])
            ->whereNull('deleted_at')
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
            ['line' => 'ONE', 'from' => 'NCT', 'to' => 'MIP', 'size' => '2X20', 'docs' => 2400, 'online' => 300, 'stack' => 0, 'expenses' => 350, 'total' => 3050],
            ['line' => 'ONE', 'from' => 'NCT', 'to' => 'MIP', 'size' => '1X40', 'docs' => 1200, 'online' => 150, 'stack' => 0, 'expenses' => 300, 'total' => 1650],
            ['line' => 'ONE', 'from' => 'NCT', 'to' => 'ECD', 'size' => '1X40', 'docs' => 800, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 1150],
            ['line' => 'ONE', 'from' => 'NCT', 'to' => 'PIER16PSACC', 'size' => '1X20', 'docs' => 1200, 'online' => 0, 'stack' => 0, 'expenses' => 520, 'total' => 1720],
            ['line' => 'ONE', 'from' => 'NCT', 'to' => 'PIER16PSACC', 'size' => '2X20', 'docs' => 2400, 'online' => 0, 'stack' => 0, 'expenses' => 520, 'total' => 2920],
            ['line' => 'ONE', 'from' => 'NCT', 'to' => 'PIER16LORENZO', 'size' => '2X20', 'docs' => 2400, 'online' => 0, 'stack' => 0, 'expenses' => 520, 'total' => 2920],
            ['line' => 'ONE', 'from' => 'NCT', 'to' => 'PIER16LORENZO', 'size' => '1X40', 'docs' => 1200, 'online' => 0, 'stack' => 0, 'expenses' => 520, 'total' => 1720],
            ['line' => 'ONE', 'from' => 'SEACON', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1200, 'online' => 340, 'stack' => 0, 'expenses' => 350, 'total' => 1890],
            ['line' => 'ONE', 'from' => 'SEACON', 'to' => 'MIP', 'size' => '1X40', 'docs' => 600, 'online' => 170, 'stack' => 0, 'expenses' => 300, 'total' => 1070],
            ['line' => 'ONE', 'from' => 'SEACON', 'to' => 'ECD', 'size' => '1X40', 'docs' => 600, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 950],
            ['line' => 'ONE', 'from' => 'SEACON', 'to' => 'PIER16PSACC', 'size' => '2X20', 'docs' => 1200, 'online' => 0, 'stack' => 0, 'expenses' => 650, 'total' => 1850],
            ['line' => 'ONE', 'from' => 'SEACON', 'to' => 'PIER16LORENZO', 'size' => '2X20', 'docs' => 1200, 'online' => 0, 'stack' => 0, 'expenses' => 670, 'total' => 1870],
            ['line' => 'ONE', 'from' => 'SEACON', 'to' => 'PIER16LORENZO', 'size' => '1X40', 'docs' => 600, 'online' => 0, 'stack' => 0, 'expenses' => 220, 'total' => 820],
            // ONE (SMY totals include stack run)
            ['line' => 'ONE', 'from' => 'NCT', 'to' => 'SMY', 'size' => '1X40', 'docs' => 1050, 'online' => 150, 'stack' => 1344, 'expenses' => null, 'total' => 3254],
            ['line' => 'ONE', 'from' => 'SEACON', 'to' => 'SMY', 'size' => '1X40', 'docs' => 500, 'online' => 0, 'stack' => 1344, 'expenses' => null, 'total' => 2424],
            ['line' => 'ONE', 'from' => 'MIP', 'to' => 'NCT', 'size' => '1X40', 'docs' => 1200, 'online' => 0, 'stack' => 0, 'expenses' => 470, 'total' => 1670],
            ['line' => 'ONE', 'from' => 'MIP', 'to' => 'SEACON', 'size' => '1X40', 'docs' => 600, 'online' => 0, 'stack' => 0, 'expenses' => 330, 'total' => 930],

            // OOCL
            ['line' => 'OOCL', 'from' => 'TOCSI', 'to' => 'SOUTH', 'size' => '2X20', 'docs' => 1350, 'online' => 0, 'stack' => 0, 'expenses' => 680, 'total' => 2030],
            ['line' => 'OOCL', 'from' => 'TOCSI', 'to' => 'SOUTH', 'size' => '1X40', 'docs' => 650, 'online' => 0, 'stack' => 0, 'expenses' => 500, 'total' => 1150],
            ['line' => 'OOCL', 'from' => 'TOCSI', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1300, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 1650],
            ['line' => 'OOCL', 'from' => 'TOCSI', 'to' => 'MIP', 'size' => '1X40', 'docs' => 650, 'online' => 0, 'stack' => 0, 'expenses' => 270, 'total' => 920],
            ['line' => 'OOCL', 'from' => 'TOCSI', 'to' => 'PIER16TRANSASIA', 'size' => '2X20', 'docs' => 1000, 'online' => 0, 'stack' => 0, 'expenses' => 550, 'total' => 1550],
            ['line' => 'OOCL', 'from' => 'TOCSI', 'to' => 'CAVITE', 'size' => '1X40', 'docs' => 650, 'online' => 0, 'stack' => 0, 'expenses' => 0, 'total' => 650],
            ['line' => 'OOCL', 'from' => 'IRS', 'to' => 'CAVITE', 'size' => '1X40', 'docs' => 800, 'online' => 0, 'stack' => 0, 'expenses' => 650, 'total' => 1450],
            ['line' => 'OOCL', 'from' => 'TOCSI', 'to' => 'SEACON', 'size' => '1X20', 'docs' => 1700, 'online' => 0, 'stack' => 0, 'expenses' => 550, 'total' => 2250],
            ['line' => 'OOCL', 'from' => 'TOCSI', 'to' => 'SEACON', 'size' => '2X20', 'docs' => 2000, 'online' => 0, 'stack' => 0, 'expenses' => 550, 'total' => 2550],
            ['line' => 'OOCL', 'from' => 'SEACON', 'to' => 'SOUTH', 'size' => '1X40', 'docs' => 1000, 'online' => 0, 'stack' => 0, 'expenses' => 550, 'total' => 1550],
            ['line' => 'OOCL', 'from' => 'SEACON', 'to' => 'MIP', 'size' => '2X20', 'docs' => 2000, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 2350],
            ['line' => 'OOCL', 'from' => 'SEACON', 'to' => 'MIP', 'size' => '1X40', 'docs' => 1000, 'online' => 0, 'stack' => 0, 'expenses' => 300, 'total' => 1300],
            ['line' => 'OOCL', 'from' => 'OCEANBOX', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1400, 'online' => 0, 'stack' => 0, 'expenses' => 270, 'total' => 1670],
            ['line' => 'OOCL', 'from' => 'OCEANBOX', 'to' => 'MIP', 'size' => '1X40', 'docs' => 700, 'online' => 0, 'stack' => 0, 'expenses' => 220, 'total' => 920],
            ['line' => 'OOCL', 'from' => 'MIP', 'to' => 'TOCSI', 'size' => '1X40', 'docs' => 500, 'online' => 0, 'stack' => 0, 'expenses' => 490, 'total' => 990],

            // KMTC
            ['line' => 'KMTC', 'from' => 'MARINA', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1600, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 1950],
            ['line' => 'KMTC', 'from' => 'MARINA', 'to' => 'MIP', 'size' => '1X40', 'docs' => 800, 'online' => 0, 'stack' => 0, 'expenses' => 300, 'total' => 1100],
            ['line' => 'KMTC', 'from' => 'NCT', 'to' => 'MIP', 'size' => '2X20', 'docs' => 2700, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 3050],
            ['line' => 'KMTC', 'from' => 'NCT', 'to' => 'MIP', 'size' => '1X40', 'docs' => 1350, 'online' => 0, 'stack' => 0, 'expenses' => 300, 'total' => 1650],
            ['line' => 'KMTC', 'from' => 'SEACON', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1200, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 1550],
            ['line' => 'KMTC', 'from' => 'SEACON', 'to' => 'MIP', 'size' => '1X40', 'docs' => 1200, 'online' => 0, 'stack' => 0, 'expenses' => 300, 'total' => 1500],
            ['line' => 'KMTC', 'from' => 'BRIGHTPOINT', 'to' => 'MIP', 'size' => '2X20', 'docs' => 2200, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 2550],
            ['line' => 'KMTC', 'from' => 'BRIGHTPOINT', 'to' => 'MIP', 'size' => '1X40', 'docs' => 1100, 'online' => 0, 'stack' => 0, 'expenses' => 300, 'total' => 1400],
            ['line' => 'KMTC', 'from' => 'SOUTH', 'to' => 'MIP', 'size' => '1X40', 'docs' => 450, 'online' => 0, 'stack' => 0, 'expenses' => 0, 'total' => 450],

            // IAL
            ['line' => 'IAL', 'from' => 'OCEANBOX', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1400, 'online' => 0, 'stack' => 0, 'expenses' => 270, 'total' => 1670],
            ['line' => 'IAL', 'from' => 'OCEANBOX', 'to' => 'MIP', 'size' => '1X40', 'docs' => 700, 'online' => 0, 'stack' => 0, 'expenses' => 220, 'total' => 920],

            // AMC
            ['line' => 'AMC', 'from' => 'OCEANBOX', 'to' => 'SOUTH', 'size' => '2X20', 'docs' => 1400, 'online' => 0, 'stack' => 0, 'expenses' => 270, 'total' => 1670],
            ['line' => 'AMC', 'from' => 'OCEANBOX', 'to' => 'SOUTH', 'size' => '1X40', 'docs' => 700, 'online' => 0, 'stack' => 0, 'expenses' => 220, 'total' => 920],

            // TS LINE
            ['line' => 'TS LINE', 'from' => 'OCEANBOX', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1400, 'online' => 0, 'stack' => 0, 'expenses' => 270, 'total' => 1670],
            ['line' => 'TS LINE', 'from' => 'OCEANBOX', 'to' => 'MIP', 'size' => '1X40', 'docs' => 700, 'online' => 0, 'stack' => 0, 'expenses' => 220, 'total' => 920],

            // SINO TRANS
            ['line' => 'SINO TRANS', 'from' => 'OCEANBOX', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1400, 'online' => 0, 'stack' => 0, 'expenses' => 270, 'total' => 1670],
            ['line' => 'SINO TRANS', 'from' => 'OCEANBOX', 'to' => 'MIP', 'size' => '1X40', 'docs' => 700, 'online' => 0, 'stack' => 0, 'expenses' => 220, 'total' => 920],

            // MSC
            ['line' => 'MSC', 'from' => 'SEACON', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1200, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 1550],
            ['line' => 'MSC', 'from' => 'SEACON', 'to' => 'MIP', 'size' => '1X40', 'docs' => 600, 'online' => 0, 'stack' => 0, 'expenses' => 300, 'total' => 900],
            ['line' => 'MSC', 'from' => 'IRS', 'to' => 'MIP', 'size' => '2X20', 'docs' => 1600, 'online' => 0, 'stack' => 0, 'expenses' => 320, 'total' => 1920],
            ['line' => 'MSC', 'from' => 'IRS', 'to' => 'MIP', 'size' => '1X40', 'docs' => 800, 'online' => 0, 'stack' => 0, 'expenses' => 270, 'total' => 1070],

            // MEDLOG
            ['line' => 'MEDLOG', 'from' => 'MEDLOG', 'to' => 'MIP', 'size' => '2X20', 'docs' => 0, 'online' => 0, 'stack' => 0, 'expenses' => 150, 'total' => 150],
            ['line' => 'MEDLOG', 'from' => 'MEDLOG', 'to' => 'MIP', 'size' => '1X40', 'docs' => 0, 'online' => 0, 'stack' => 0, 'expenses' => 100, 'total' => 100],

            // NCT
            ['line' => 'NCT', 'from' => 'NCT', 'to' => 'MIP', 'size' => '2X20', 'docs' => 0, 'online' => 0, 'stack' => 0, 'expenses' => 350, 'total' => 350],
            ['line' => 'NCT', 'from' => 'NCT', 'to' => 'MIP', 'size' => '1X40', 'docs' => 0, 'online' => 0, 'stack' => 0, 'expenses' => 300, 'total' => 300],

            // CMA CGM
            ['line' => 'CMA CGM', 'from' => 'MNHPI', 'to' => 'MILT', 'size' => '1X40', 'docs' => 850, 'online' => 0, 'stack' => 0, 'expenses' => 700, 'total' => 1550],
        ];

        foreach ($rows as $row) {
            $shippingLineId = $this->resolveShippingLineId($row['line'], $shippingLineIdByShortName, $shippingLineIdByName);
            if (! $shippingLineId) {
                $this->command?->warn("Skipping fixed_expenses for '{$row['line']}': shipping line not found.");
                continue;
            }

            $fromId = $cypaIdByShortName[$row['from']] ?? null;
            $toId = $cypaIdByShortName[$row['to']] ?? null;
            if (! $fromId || ! $toId) {
                $this->command?->warn("Skipping '{$row['line']}' {$row['from']}->{$row['to']}: CYPA code not found.");
                continue;
            }

            $containerSize = $this->normalizeContainerSize($row['size']);
            if (! $containerSize) {
                $this->command?->warn("Skipping '{$row['line']}' {$row['from']}->{$row['to']}: invalid size '{$row['size']}'.");
                continue;
            }

            $docsFee = $this->toDecimal($row['docs'] ?? 0);
            $online = $this->toDecimal($row['online'] ?? 0);
            $stack = $this->toDecimal($row['stack'] ?? 0);
            $total = $this->toDecimal($row['total'] ?? 0);

            $expenses = array_key_exists('expenses', $row) && $row['expenses'] !== null
                ? $this->toDecimal($row['expenses'])
                : max(0.00, $total - ($docsFee + $online + $stack));

            $payload = [
                'shipping_line_id' => $shippingLineId,
                'cypa_id_from' => $fromId,
                'cypa_id_to' => $toId,
                'container_size' => $containerSize,
                'docs_fee' => $docsFee,
                'online_booking_fee' => $online,
                'stack_run' => $stack,
                'expenses' => $expenses,
                'total_expenses' => $total > 0 ? $total : ($docsFee + $online + $stack + $expenses),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('fixed_expenses')->updateOrInsert(
                [
                    'shipping_line_id' => $payload['shipping_line_id'],
                    'cypa_id_from' => $payload['cypa_id_from'],
                    'cypa_id_to' => $payload['cypa_id_to'],
                    'container_size' => $payload['container_size'],
                ],
                $payload
            );
        }
    }

    private function resolveShippingLineId(string $key, array $idByShortName, array $idByName): ?int
    {
        if (isset($idByShortName[$key])) {
            return (int) $idByShortName[$key];
        }

        return isset($idByName[$key]) ? (int) $idByName[$key] : null;
    }

    private function normalizeContainerSize(string $size): ?string
    {
        $s = strtoupper(trim($size));
        if ($s === '') {
            return null;
        }

        if (str_contains($s, '40')) {
            return '40ft';
        }

        if (str_contains($s, '20')) {
            return '20ft';
        }

        return null;
    }

    private function toDecimal(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return 0.00;
        }

        $s = str_replace([',', ' '], '', $s);
        if (! is_numeric($s)) {
            return 0.00;
        }

        return (float) $s;
    }
}

