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
            $table->bigInteger('shipping_line_id')->unsigned(); //client input
            $table->bigInteger('cypa_id_from')->unsigned(); //client input
            $table->bigInteger('cypa_id_to')->unsigned(); //client input
            $table->string('container_size'); //client input
            $table->decimal('docs_fee', 10, 2)->default(0); //client input - amount value
            $table->decimal('stack_run', 10, 2)->default(0); //client input - amount value
            $table->decimal('expenses', 10, 2)->default(0); //client input - amount value
            $table->decimal('total_expenses', 10, 2)->default(0); //this is auto compute based on docs_fee + stack_run + expenses
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

        Schema::dropIfExists('fixed_expenses');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};

