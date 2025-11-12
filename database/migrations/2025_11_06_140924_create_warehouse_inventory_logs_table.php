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

        Schema::create('warehouse_inventory_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('warehouse_log_id');
            $table->bigInteger('warehouse_id')->unsigned();
            $table->text('warehouse_log_description')->nullable();
            $table->integer('warehouse_log_stock_quantity')->default(0);
            $table->string('warehouse_log_unit_measure')->nullable();
            $table->string('warehouse_log_type')->nullable();
            $table->date('warehouse_log_date');
            $table->string('fleet_truck_plate_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouse_inventory')->onDelete('cascade');
            $table->foreign('fleet_truck_plate_number')->references('fleet_truck_plate_number')->on('fleet_trucks')->onDelete('set null');

            // Indexes
            $table->index('warehouse_id');
            $table->index('warehouse_log_date');
            $table->index('warehouse_log_type');
            $table->index('fleet_truck_plate_number');
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
        
        Schema::dropIfExists('warehouse_inventory_logs');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
