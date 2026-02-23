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

        Schema::create('parts_expense', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('shift')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('receipt_no')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('article')->nullable();
            $table->decimal('amount_per_item', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('plate_number')
                ->references('plate_number')
                ->on('fleet_trucks')
                ->onDelete('set null');

            // Indexes
            $table->index('shift');
            $table->index('plate_number', 'idx_plate_number');
            $table->index('transaction_date', 'idx_transaction_date');
            $table->index('receipt_no', 'idx_receipt_no');
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

        Schema::dropIfExists('parts_expense');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
