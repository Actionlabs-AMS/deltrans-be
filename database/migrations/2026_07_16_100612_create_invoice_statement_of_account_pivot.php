<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Moves invoices from a single statement_of_account_id to a many-to-many pivot.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('invoice_statement_of_account', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('statement_of_account_id');
            $table->timestamps();

            $table->foreign('invoice_id')
                ->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('statement_of_account_id')
                ->references('id')->on('statement_of_accounts')->onDelete('restrict');

            $table->unique(['invoice_id', 'statement_of_account_id'], 'invoice_soa_unique');
            $table->index('invoice_id');
            $table->index('statement_of_account_id');
        });

        $now = now();
        $rows = DB::table('invoices')
            ->whereNotNull('statement_of_account_id')
            ->select('id', 'statement_of_account_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('invoice_statement_of_account')->insert([
                'invoice_id' => $row->id,
                'statement_of_account_id' => $row->statement_of_account_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['statement_of_account_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('statement_of_account_id');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('statement_of_account_id')->nullable()->after('id');
        });

        $pivots = DB::table('invoice_statement_of_account')
            ->orderBy('id')
            ->get()
            ->groupBy('invoice_id');

        foreach ($pivots as $invoiceId => $links) {
            $firstSoaId = $links->first()->statement_of_account_id;
            DB::table('invoices')
                ->where('id', $invoiceId)
                ->update(['statement_of_account_id' => $firstSoaId]);
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('statement_of_account_id')
                ->references('id')->on('statement_of_accounts')->onDelete('restrict');
            $table->index('statement_of_account_id');
        });

        Schema::dropIfExists('invoice_statement_of_account');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
