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
            $table->string('waybill_number')->primary();
            $table->date('transaction_date');
            $table->string('shipping_line_email_address'); //remove
            $table->bigInteger('shipping_line_id')->nullable()->unsigned();
            $table->bigInteger('cypa_id')->nullable()->unsigned();
            $table->bigInteger('driver_id')->nullable()->unsigned();
            $table->bigInteger('helper_id')->nullable()->unsigned();
            $table->string('truck_plate_number')->nullable();
            $table->bigInteger('fixed_expense_id')->nullable()->unsigned();
            $table->string('container_size')->nullable();
            $table->decimal('other_expense', 15, 2)->default(0);
            $table->string('container_id')->nullable();
            $table->date('pickup_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->decimal('post_expense_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('set null');
            $table->foreign('cypa_id')->references('id')->on('cypa_details')->onDelete('set null');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
            $table->foreign('helper_id')->references('id')->on('helpers')->onDelete('set null');
            $table->foreign('truck_plate_number')->references('plate_number')->on('fleet_trucks')->onDelete('set null');
            $table->foreign('fixed_expense_id')->references('id')->on('fixed_expenses')->onDelete('set null');

            // Indexes for better query performance
            $table->index('transaction_date');
            $table->index('cypa_id');
            $table->index('driver_id');
            $table->index('helper_id');
            $table->index('truck_plate_number');
            $table->index('shipping_line_id');
            $table->index('fixed_expense_id');
            $table->index('container_id');
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
