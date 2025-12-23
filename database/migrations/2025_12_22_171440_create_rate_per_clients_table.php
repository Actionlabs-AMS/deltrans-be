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
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->integer('no_of_days');
            $table->string('requirements')->nullable();
            $table->string('remarks')->nullable();
            $table->bigInteger('cypa_id')->unsigned()->default(0); // 0 = all
            $table->bigInteger('stack_run_id')->unsigned()->nullable();
            $table->string('size'); // 20ft / 40ft / 20ft(offhire) / 40ft(offhire)
            $table->integer('rate');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            // Note: cypa_id default 0 means "all", so no foreign key constraint
            // If cypa_id > 0, it references cypa_details.id
            $table->foreign('stack_run_id')->references('id')->on('stack_runs')->onDelete('set null');

            // Indexes
            $table->index('shipping_line_id');
            $table->index('cypa_id');
            $table->index('stack_run_id');
            $table->index('size');
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
