<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Creates billing_statements (after statement_of_accounts exists) with statement_of_account_id FK.
     * Idempotent: if table already exists (e.g. from older install), only adds FK.
     *
     * Existing installs that had 2025_11_06_140925_create_billing_statements_table and
     * 2026_02_06_000000_add_billing_statements_soa_foreign_key: remove those rows from the
     * migrations table, then run migrate so this migration runs (adds FK only).
     */
    public function up(): void
    {
        if (Schema::hasTable('billing_statements')) {
            $this->addStatementOfAccountForeignKeyIfMissing();
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('billing_statements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('statement_of_account_id')->unsigned();
            $table->bigInteger('prepared_by')->unsigned();
            $table->string('billing_statement_no');
            $table->string('payment_term')->nullable();
            $table->date('ci_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('bus_style')->nullable();
            $table->boolean('has_details')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('statement_of_account_id')
                ->references('id')->on('statement_of_accounts')->onDelete('restrict');
            $table->foreign('prepared_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('statement_of_account_id');
            $table->index('prepared_by');
            $table->index('billing_statement_no');
            $table->index('ci_date');
            $table->index('due_date');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        $this->dropStatementOfAccountForeignKeyIfExists();
        Schema::dropIfExists('billing_statements');
    }

    private function addStatementOfAccountForeignKeyIfMissing(): void
    {
        $fkExists = DB::select(
            "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE k
             INNER JOIN information_schema.TABLE_CONSTRAINTS c
               ON k.CONSTRAINT_SCHEMA = c.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = ? AND k.TABLE_NAME = ? AND k.COLUMN_NAME = ?
               AND c.CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [DB::getDatabaseName(), 'billing_statements', 'statement_of_account_id']
        );
        if (count($fkExists) > 0) {
            return;
        }
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->foreign('statement_of_account_id')
                ->references('id')->on('statement_of_accounts')->onDelete('restrict');
        });
    }

    private function dropStatementOfAccountForeignKeyIfExists(): void
    {
        $rows = DB::select(
            "SELECT k.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE k
             INNER JOIN information_schema.TABLE_CONSTRAINTS c
               ON k.CONSTRAINT_SCHEMA = c.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = ? AND k.TABLE_NAME = ? AND k.COLUMN_NAME = ?
               AND c.CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [DB::getDatabaseName(), 'billing_statements', 'statement_of_account_id']
        );
        if (count($rows) === 0) {
            return;
        }
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->dropForeign(['statement_of_account_id']);
        });
    }
};

//todo: Billing: set is_paid to true if we generate invoice