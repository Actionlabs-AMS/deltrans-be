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

        Schema::create('waybill_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id'); // primary key
            $table->string('waybill_number')->unique(); // unique identifier, fillable from user input
            $table->date('transaction_date'); // this should be fillable from user input
            $table->bigInteger('shipping_line_id')->nullable()->unsigned(); // this should be fillable from user input
            $table->bigInteger('stack_run_id')->nullable()->unsigned();  // this should be fillable from user input
            //$table->bigInteger('cypa_id')->nullable()->unsigned();
            $table->bigInteger('driver_id')->nullable()->unsigned(); // this should be fillable from user input, foreign key to drivers table
            $table->bigInteger('helper_id')->nullable()->unsigned(); // this should be fillable from user input, foreign key to helpers table
            $table->string('truck_plate_number')->nullable(); // this should be fillable from user input, foreign key to fleet_trucks table
            $table->bigInteger('fixed_expense_id')->nullable()->unsigned(); // this should be fillable from user input, foreign key to fixed_expenses table
            $table->bigInteger('rate_per_client_id')->nullable()->unsigned(); // this should be fillable from user input, foreign key to fixed_expenses table 
            $table->decimal('other_expense', 15, 2)->default(0); // this should be fillable from user input
            $table->date('pickup_date')->nullable(); // this should be fillable from user input
            $table->date('delivered_date')->nullable(); // this should be fillable from user input
            $table->decimal('post_expense_amount', 15, 2)->default(0); // this should be fillable from user input
            $table->decimal('total_amount', 15, 2)->default(0); // this should be fillable from user input
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('set null');
            $table->foreign('stack_run_id')->references('id')->on('stack_runs')->onDelete('set null');
            //$table->foreign('cypa_id')->references('id')->on('cypa_details')->onDelete('set null');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
            $table->foreign('helper_id')->references('id')->on('helpers')->onDelete('set null');
            $table->foreign('truck_plate_number')->references('plate_number')->on('fleet_trucks')->onDelete('set null');
            $table->foreign('fixed_expense_id')->references('id')->on('fixed_expenses')->onDelete('set null');
            $table->foreign('rate_per_client_id')->references('id')->on('rate_per_clients')->onDelete('set null');

            // Indexes for better query performance
            $table->index('transaction_date');
            $table->index('stack_run_id');
            //$table->index('cypa_id');
            $table->index('driver_id');
            $table->index('helper_id');
            $table->index('truck_plate_number');
            $table->index('shipping_line_id');
            $table->index('fixed_expense_id');
            $table->index('rate_per_client_id');
            $table->index('pickup_date');
            $table->index('delivered_date');
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

        Schema::dropIfExists('waybill_details');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
