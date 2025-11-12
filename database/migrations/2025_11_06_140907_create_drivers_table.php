<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('drivers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('driver_id');
            $table->string('driver_first_name');
            $table->string('driver_last_name');
            $table->string('driver_contact_number');
            $table->boolean('active_status')->default(true);
            $table->string('truck_plate_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('truck_plate_number')->references('plate_number')->on('trucks')->onDelete('set null');

            // Indexes
            $table->index('active_status');
            $table->index('truck_plate_number');
            $table->index(['driver_first_name', 'driver_last_name']);
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

        Schema::dropIfExists('drivers');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
