<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('discount_id');
        });

        DB::statement(<<<'SQL'
            UPDATE invoices AS i
            SET i.is_paid = CASE
                WHEN NOT EXISTS (
                    SELECT 1
                    FROM invoice_statement_of_account AS isoa
                    INNER JOIN billing_statements AS bs
                        ON bs.statement_of_account_id = isoa.statement_of_account_id
                        AND bs.deleted_at IS NULL
                    WHERE isoa.invoice_id = i.id
                        AND COALESCE(bs.is_paid, 0) = 0
                )
                THEN 1
                ELSE 0
            END
        SQL);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });
    }
};
