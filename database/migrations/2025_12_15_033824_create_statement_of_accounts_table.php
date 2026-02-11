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

        Schema::create('statement_of_accounts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->string('dli_sa_number');
            $table->json('booking_ids')->nullable()->comment('Array of booking IDs');
            $table->string(column: 'work_order')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints (booking_ids is JSON so no FK to bookings)
            $table->foreign('shipping_line_id')
                ->references('id')
                ->on('shipping_lines')
                ->onDelete('cascade');

            // Indexes
            $table->index('shipping_line_id');
            $table->index('dli_sa_number');
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

        Schema::dropIfExists('statement_of_accounts');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};