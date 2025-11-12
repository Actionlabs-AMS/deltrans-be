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

        Schema::create('budget_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('budget_id');
            $table->decimal('budget_total_amount', 15, 2)->default(0);
            $table->bigInteger('budget_transaction_id')->unsigned();
            $table->text('description')->nullable();
            $table->date('tracked_date')->nullable();
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->string('expense_type')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('budget_transaction_id')->references('budget_transaction_id')->on('budget_transactions')->onDelete('cascade');

            // Indexes
            $table->index('budget_transaction_id');
            $table->index('tracked_date');
            $table->index('expense_type');
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
        
        Schema::dropIfExists('budget_history');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
