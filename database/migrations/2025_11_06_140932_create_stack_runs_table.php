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
            $table->integer('quantity_of_container')->default(0);
            $table->string('container_size')->nullable();
            $table->integer('expected_no_of_waybill')->default(0); // Calculated based on quantity_of_container: 1 waybill = 2 20ft containers OR 1 waybill = 1 40ft container
            $table->bigInteger('cypa_id_from')->unsigned();
            $table->bigInteger('cypa_id_to')->unsigned();
            $table->json('waybill_number')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('cypa_id_from')->references('id')->on('cypa_details')->onDelete('cascade');
            $table->foreign('cypa_id_to')->references('id')->on('cypa_details')->onDelete('cascade');

            // Indexes
            $table->index('shipping_line_id');
            $table->index('cypa_id_from');
            $table->index('cypa_id_to');
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

