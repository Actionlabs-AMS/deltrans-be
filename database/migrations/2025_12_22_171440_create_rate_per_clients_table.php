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

        Schema::create('rate_per_clients', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('shipping_line_id')->unsigned(); //client input
            $table->integer('no_of_days'); //client input
            $table->string('requirements')->nullable(); //client input
            $table->string('remarks')->nullable(); //client input
            $table->bigInteger('cypa_id')->unsigned()->default(0); // 0 = all //client input
            $table->decimal('stack_run', 10, 2); //client input - amount value
            $table->string('size'); // 20ft / 40ft / 20ft(offhire) / 40ft(offhire)
            $table->decimal('rate', 10, 2); //client input - amount value
            $table->tinyInteger('is_active')->default(1); //client input
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            // Note: cypa_id default 0 means "all", so no foreign key constraint
            // If cypa_id > 0, it references cypa_details.id
            // Note: stack_run is an amount value, not a foreign key

            // Indexes
            $table->index('shipping_line_id');
            $table->index('cypa_id');
            $table->index('size');
            $table->index('is_active');
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

        Schema::dropIfExists('rate_per_clients');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
