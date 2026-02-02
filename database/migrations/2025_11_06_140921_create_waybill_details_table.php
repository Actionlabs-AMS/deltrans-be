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
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('waybill_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements(column: 'id');
            $table->string('waybill_number')->unique();
            $table->date('transaction_date');
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->bigInteger('booking_id')->unsigned();
            $table->bigInteger('driver_id')->unsigned();
            $table->json('helper_id')->nullable(); // JSON field for multiple helper IDs
            $table->string('container_size');
            $table->string('container_type')->nullable();
            $table->string('truck_plate_number');
            $table->bigInteger('fixed_expense_id')->unsigned();
            $table->bigInteger('rate_per_client_id')->unsigned()->nullable(); // nullable means no rate per client
            $table->date('pickup_date');
            $table->date('delivered_date');
            $table->decimal('post_expense_amount', 15, 2)->default(0);
            $table->decimal('total_rate_per_client', 15, 2)->default(0); //fields
            $table->decimal('total_expense', 15, 2)->default(0); //fields
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
            $table->foreign('truck_plate_number')->references('plate_number')->on('fleet_trucks')->onDelete('cascade');
            $table->foreign('fixed_expense_id')->references('id')->on('fixed_expenses')->onDelete('cascade');
            $table->foreign('rate_per_client_id')->references('id')->on('rate_per_clients')->onDelete('cascade');

            $table->index('transaction_date');
            $table->index('booking_id');
            $table->index('driver_id');
            $table->index('truck_plate_number');
            $table->index('shipping_line_id');
            $table->index('fixed_expense_id');
            $table->index('rate_per_client_id');
            $table->index('pickup_date');
            $table->index('delivered_date');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('waybill_details');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
