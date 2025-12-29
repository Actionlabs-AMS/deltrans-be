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

        Schema::create('fixed_expenses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->bigInteger('cypa_id_from')->unsigned();
            $table->bigInteger('cypa_id_to')->unsigned();
            $table->string('container_size');
            $table->decimal('docs_fee', 10, 2)->default(0);
            $table->decimal('stack_run', 10, 2)->default(0);
            $table->decimal('expenses', 10, 2)->default(0);
            $table->decimal('total_expenses', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cypa_id_from')->references('id')->on('cypa_details')->onDelete('cascade');
            $table->foreign('cypa_id_to')->references('id')->on('cypa_details')->onDelete('cascade');
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');

            $table->index('cypa_id_from');
            $table->index('cypa_id_to');
            $table->index('shipping_line_id');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('fixed_expenses');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};

