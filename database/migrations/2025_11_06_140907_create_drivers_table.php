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
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('contact_number');
            $table->json('assigned_truck_plate_numbers')->nullable();
            $table->json('stack_run')->nullable();
            $table->json('helpers_id')->nullable();
            $table->boolean('active_status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('active_status');
            $table->index(['first_name', 'last_name']);
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
