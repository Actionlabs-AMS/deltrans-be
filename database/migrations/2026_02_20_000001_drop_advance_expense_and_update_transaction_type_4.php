<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Type 4 (Advance Expense) now uses driver_cash_advancement_history and
     * helper_cash_advancement_history (based on type 1=driver, 2=helper).
     */
    public function up(): void
    {
        if (Schema::hasTable('advance_expense')) {
            Schema::dropIfExists('advance_expense');
        }

        DB::table('transaction_types')->where('type', 4)->update([
            'detail_table' => 'driver_cash_advancement_history',
            'description' => 'Cash advance: use driver_cash_advancement_history when type=1 (driver), helper_cash_advancement_history when type=2 (helper). Fetch by joining both tables.',
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('transaction_types')->where('type', 4)->update([
            'detail_table' => 'advance_expense',
            'description' => 'Tracks cash advances given to drivers or helpers',
            'updated_at' => now(),
        ]);
        // Recreating advance_expense would require the original create migration
    }
};
