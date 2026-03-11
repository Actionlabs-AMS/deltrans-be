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

        Schema::create('truck_trip_expense', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('shift')->nullable();
            $table->string('plate_number')->nullable();
            $table->bigInteger('helper_id')->unsigned()->nullable();
            $table->decimal('cash_on_hand', 15, 2)->default(0)->comment('Current money');
            $table->decimal('issued_cash_amount', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('helper_id')
                ->references('id')
                ->on('helpers')
                ->onDelete('set null');

            // Indexes
            $table->index('shift');
            $table->index('plate_number');
            $table->index('helper_id', 'idx_helper_id');
            $table->index('transaction_date', 'idx_transaction_date');
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

        Schema::dropIfExists('truck_trip_expense');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
