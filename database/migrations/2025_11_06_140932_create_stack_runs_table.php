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
            $table->bigIncrements('id'); // auto increment
            $table->string('reference_number')->nullable(); // fillable from user input
            $table->bigInteger('shipping_line_id')->unsigned(); // user input
            $table->integer('quantity_of_container')->default(0); // user input
            $table->string('container_size')->nullable(); // fillable from user input
            $table->bigInteger('cypa_id_from')->unsigned(); // fillable from user input
            $table->bigInteger('cypa_id_to')->unsigned(); // fillable from user input
            $table->decimal('total_amount', 15, 2)->default(0.00)->nullable(false); // this is not fillable for this api, other api will fill this field
            $table->tinyInteger('is_complete')->default(0)->nullable(false); // this is not fillable for this api, other api will fill this field
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('cypa_id_from')->references('id')->on('cypa_details')->onDelete('cascade');
            $table->foreign('cypa_id_to')->references('id')->on('cypa_details')->onDelete('cascade');

            // Indexes
            $table->index('reference_number');
            $table->index('shipping_line_id');
            $table->index('cypa_id_from');
            $table->index('cypa_id_to');
            $table->index('is_complete');
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

