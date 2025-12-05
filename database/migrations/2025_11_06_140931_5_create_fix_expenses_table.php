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

        Schema::create('fixed_expenses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->bigInteger('cypa_id_from')->unsigned();
            $table->bigInteger('cypa_id_to')->unsigned();
            $table->string('container_size');
            $table->integer('docs_fee')->default(0);
            $table->integer('stack_run')->default(0);
            $table->integer('expenses')->default(0);
            $table->integer('total_expenses')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('cypa_id_from')->references('id')->on('cypa_details')->onDelete('cascade');
            $table->foreign('cypa_id_to')->references('id')->on('cypa_details')->onDelete('cascade');
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            // Indexes
            $table->index('cypa_id_from');
            $table->index('cypa_id_to');
            $table->index('shipping_line_id');
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

        Schema::dropIfExists('fix_expenses');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};

