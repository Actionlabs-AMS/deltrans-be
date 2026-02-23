<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Remove computed and line-item fields from invoices. Values are computed
     * when generating PDF (download/send email) from SOA/waybill data.
     */
    public function up(): void
    {
        $columns = [
            'quantity',
            'unit_price',
            'item_description',
            'vatable_sales',
            'zero_rated_sales',
            'vat_exempt_sales',
            'vat',
            'total_sales',
            'less_vat',
            'net_of_vat',
            'less_withdrawing_tax',
            'total_amount',
        ];
        $toDrop = array_filter($columns, fn (string $col) => Schema::hasColumn('invoices', $col));
        if (empty($toDrop)) {
            return;
        }
        Schema::table('invoices', function (Blueprint $table) use ($toDrop) {
            $table->dropColumn($toDrop);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->after('discount_id');
            $table->decimal('unit_price', 15, 2)->default(0)->after('quantity');
            $table->text('item_description')->nullable()->after('unit_price');
            $table->decimal('vatable_sales', 15, 2)->default(0)->after('item_description');
            $table->decimal('zero_rated_sales', 15, 2)->default(0)->after('vatable_sales');
            $table->decimal('vat_exempt_sales', 15, 2)->default(0)->after('zero_rated_sales');
            $table->decimal('vat', 15, 2)->default(0)->after('vat_exempt_sales');
            $table->decimal('total_sales', 15, 2)->default(0)->after('vat');
            $table->decimal('less_vat', 15, 2)->default(0)->after('total_sales');
            $table->decimal('net_of_vat', 15, 2)->default(0)->after('less_vat');
            $table->decimal('less_withdrawing_tax', 15, 2)->default(0)->after('net_of_vat');
            $table->decimal('total_amount', 15, 2)->default(0)->after('less_withdrawing_tax');
        });
    }
};
