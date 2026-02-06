<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds foreign key from billing_statements.statement_of_account_id to statement_of_accounts.id.
     * Defined in a separate migration because statement_of_accounts is created after billing_statements.
     */
    public function up(): void
    {
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->foreign('statement_of_account_id')
                ->references('id')
                ->on('statement_of_accounts')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->dropForeign(['statement_of_account_id']);
        });
    }
};
