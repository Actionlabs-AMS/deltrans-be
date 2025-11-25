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

        Schema::create('stack_runs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->bigInteger('fixed_expense_id')->nullable()->unsigned();
            $table->integer('quantity_of_container')->default(0);
            $table->bigInteger('drive_id')->unsigned();
            $table->bigInteger('fleet_truck_id')->unsigned();
            $table->json('waybill')->nullable();
            $table->bigInteger('other_expense')->nullable()->unsigned();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('fixed_expense_id')->references('id')->on('fixed_expenses')->onDelete('cascade');
            $table->foreign('drive_id')->references('id')->on('drivers')->onDelete('cascade');
            $table->foreign('fleet_truck_id')->references('id')->on('fleet_trucks')->onDelete('cascade');
            $table->foreign('other_expense')->references('request_id')->on('requests')->onDelete('set null');

            // Indexes
            $table->index('shipping_line_id');
            $table->index('fixed_expense_id');
            $table->index('cypa_id_from');
            $table->index('cypa_id_to');
            $table->index('drive_id');
            $table->index('fleet_truck_id');
            $table->index('other_expense');
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

        Schema::dropIfExists('stack_runs');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};

