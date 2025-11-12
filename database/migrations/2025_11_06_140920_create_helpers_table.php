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

        Schema::create('helpers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('helper_id');
            $table->string('helper_first_name');
            $table->string('helper_last_name');
            $table->string('helper_contact_number');
            $table->decimal('helper_remaining_fund', 15, 2)->default(0);
            $table->boolean('active_status')->default(true);
            $table->string('fleet_truck_plate_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('fleet_truck_plate_number')->references('fleet_truck_plate_number')->on('fleet_trucks')->onDelete('set null');

            // Indexes
            $table->index('active_status');
            $table->index('fleet_truck_plate_number');
            $table->index(['helper_first_name', 'helper_last_name']);
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
        
        Schema::dropIfExists('helpers');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
