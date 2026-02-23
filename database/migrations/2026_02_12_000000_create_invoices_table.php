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
            $table->decimal('discount', 15, 2)->default(0);
            $table->bigInteger('discount_id')->unsigned()->nullable();
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
