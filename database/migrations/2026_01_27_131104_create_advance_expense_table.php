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

        Schema::create('advance_expense', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('budget_transaction_id')->unsigned();
            $table->tinyInteger('employee_type')->default(1)->comment('1 = driver, 2 = helper');
            $table->bigInteger('driver_helper_id')->unsigned()->nullable()->comment('Driver or Helper ID based on employee_type');
            $table->decimal('cash_advance_given_today', 15, 2)->default(0);
            $table->date('date_issued');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('budget_transaction_id')
                ->references('id')
                ->on('budget_transactions')
                ->onDelete('cascade');

            // Note: driver_helper_id can reference either drivers or helpers table
            // This is handled at application level since we can't have conditional foreign keys

            // Indexes
            $table->index('budget_transaction_id', 'idx_budget_transaction_id');
            $table->index('employee_type', 'idx_employee_type');
            $table->index('driver_helper_id', 'idx_driver_helper_id');
            $table->index('date_issued', 'idx_date_issued');
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

        Schema::dropIfExists('advance_expense');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
