<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$migrationsToRemove = [
    '2025_11_06_140925_create_billing_statements_table',
    '2026_02_06_000000_add_billing_statements_soa_foreign_key',
    '2026_02_06_000001_drop_shipping_line_and_booking_from_billing_statements',
];

$deleted = DB::table('migrations')
    ->whereIn('migration', $migrationsToRemove)
    ->delete();

echo "Deleted {$deleted} old migration entries from migrations table.\n";
echo "Migrations removed:\n";
foreach ($migrationsToRemove as $migration) {
    echo "  - {$migration}\n";
}
