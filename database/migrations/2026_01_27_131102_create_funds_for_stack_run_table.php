<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('funds_for_stack_run', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('budget_transaction_id')->unsigned();
            $table->text('remarks')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('date');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('budget_transaction_id')
                ->references('id')
                ->on('budget_transactions')
                ->onDelete('cascade');

            // Indexes
            $table->index('budget_transaction_id', 'idx_budget_transaction_id');
            $table->index('date', 'idx_date');
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('funds_for_stack_run');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
