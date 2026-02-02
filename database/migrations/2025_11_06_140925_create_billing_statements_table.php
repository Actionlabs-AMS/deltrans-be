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

        Schema::create('billing_statements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->bigInteger('booking_id')->unsigned();
            $table->bigInteger('prepared_by')->unsigned()->comment('User ID of the logged-in user');
            $table->string('billing_statement_no');
            $table->string('payment_term')->nullable();
            $table->date('ci_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('bus_style')->nullable();
            $table->boolean('has_details')->default(false); //this will identify if the billing statement has details or not
            $table->boolean('is_paid')->default(false); //this will identify if the billing statement is paid or not
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('prepared_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('shipping_line_id');
            $table->index('booking_id');
            $table->index('prepared_by');
            $table->index('billing_statement_no');
            $table->index('ci_date');
            $table->index('due_date');
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

        Schema::dropIfExists('billing_statements');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
