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

        Schema::create('stack_runs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('reference_number')->nullable();
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->integer('quantity_of_container')->default(0);
            $table->string('container_size')->nullable();
            $table->bigInteger('cypa_id_from')->unsigned();
            $table->bigInteger('cypa_id_to')->unsigned();
            $table->decimal('total_amount', 15, 2)->default(0.00)->nullable(false);
            $table->tinyInteger('is_complete')->default(0)->nullable(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('cypa_id_from')->references('id')->on('cypa_details')->onDelete('cascade');
            $table->foreign('cypa_id_to')->references('id')->on('cypa_details')->onDelete('cascade');

            $table->index('reference_number');
            $table->index('shipping_line_id');
            $table->index('cypa_id_from');
            $table->index('cypa_id_to');
            $table->index('is_complete');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('stack_runs');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};

