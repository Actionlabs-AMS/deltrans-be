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
            $table->string('waybill_number')->unique(); // field
            $table->date('transaction_date'); // field
            $table->bigInteger('shipping_line_id')->unsigned(); // field
            $table->bigInteger('booking_id')->unsigned(); // field
            $table->bigInteger('driver_id')->unsigned(); // field
            $table->bigInteger('helper_id')->unsigned()->nullable(); // field
            $table->string('container_size'); // field
            $table->string('container_type')->nullable(); // field
            $table->string('truck_plate_number'); // field
            $table->date('pickup_date'); // field
            $table->date('delivered_date'); // field

            //rate per client details
            $table->integer('no_of_days'); // field, not editable
            $table->string('requirements')->nullable(); // field, not editable
            $table->string('remarks')->nullable(); // field, not editable
            $table->decimal('stack_run', 10, 2); // field
            $table->decimal('rate', 10, 2); // field
            $table->decimal('tax_percent', 10, 2)->nullable(); // field, not editable
            $table->boolean('has_vat')->default(true); // field, not editable
            $table->decimal('total_rate_per_client', 15, 2)->default(0); // field


            //fixed expenses details
            $table->bigInteger('fixed_expense_id')->unsigned(); //auto
            $table->decimal('post_expense_amount', 15, 2)->default(0); // field
            $table->decimal('total_expense', 15, places: 2)->default(0); // field

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
            $table->foreign('helper_id')->references('id')->on('helpers')->onDelete('set null');
            $table->foreign('truck_plate_number')->references('plate_number')->on('fleet_trucks')->onDelete('cascade');
            $table->foreign('fixed_expense_id')->references('id')->on('fixed_expenses')->onDelete('cascade');

            $table->index('transaction_date');
            $table->index('booking_id');
            $table->index('driver_id');
            $table->index('helper_id');
            $table->index('truck_plate_number');
            $table->index('shipping_line_id');
            $table->index('fixed_expense_id');
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
