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

        Schema::create('helper_trip_expense_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('hteh_id');
            $table->decimal('hteh_amount', 15, 2)->default(0);
            $table->date('hteh_transaction_date');
            $table->string('hteh_shift')->nullable();
            $table->bigInteger('helper_id')->unsigned();
            $table->string('fleet_truck_plate_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('helper_id')->references('helper_id')->on('helpers')->onDelete('cascade');
            $table->foreign('fleet_truck_plate_number')->references('fleet_truck_plate_number')->on('fleet_trucks')->onDelete('set null');

            // Indexes
            $table->index('hteh_transaction_date');
            $table->index('helper_id');
            $table->index('fleet_truck_plate_number');
            $table->index('hteh_shift');
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
        
        Schema::dropIfExists('helper_trip_expense_history');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
