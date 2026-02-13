<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('invoices', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('statement_of_account_id')->unsigned();
            $table->string('invoice_number');
            $table->date('date');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->text('item_description')->nullable();
            $table->decimal('vatable_sales', 15, 2)->default(0);
            $table->decimal('zero_rated_sales', 15, 2)->default(0);
            $table->decimal('vat_exempt_sales', 15, 2)->default(0);
            $table->decimal('vat', 15, 2)->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('less_vat', 15, 2)->default(0);
            $table->decimal('net_of_vat', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->bigInteger('discount_id')->unsigned()->nullable();
            $table->decimal('less_withdrawing_tax', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('statement_of_account_id')
                ->references('id')->on('statement_of_accounts')->onDelete('restrict');

            $table->index('statement_of_account_id');
            $table->index('invoice_number');
            $table->index('date');
            $table->index('discount_id');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        Schema::dropIfExists('invoices');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
