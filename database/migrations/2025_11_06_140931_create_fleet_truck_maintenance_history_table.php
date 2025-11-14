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

        Schema::create('fleet_truck_maintenance_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('receipt_number')->nullable();
            $table->string('article')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->date('maintenance_date');
            $table->string('fleet_truck_plate_number');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('fleet_truck_plate_number')->references('plate_number')->on('fleet_trucks')->onDelete('cascade');

            // Indexes
            $table->index('maintenance_date');
            $table->index('fleet_truck_plate_number');
            $table->index('receipt_number');
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
        
        Schema::dropIfExists('fleet_truck_maintenance_history');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
