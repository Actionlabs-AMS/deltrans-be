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

        Schema::create('fleet_trucks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('fleet_truck_plate_number')->primary();
            $table->string('fleet_container_size')->nullable();
            $table->string('fleet_condition')->nullable();
            $table->string('fleet_status')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('fleet_status');
            $table->index('fleet_condition');
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
        
        Schema::dropIfExists('fleet_trucks');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
