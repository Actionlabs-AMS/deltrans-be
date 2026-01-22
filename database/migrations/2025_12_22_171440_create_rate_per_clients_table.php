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

        Schema::create('rate_per_clients', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->integer('no_of_days');
            $table->string('requirements')->nullable();
            $table->string('remarks')->nullable();
            $table->bigInteger('cypa_id')->unsigned()->default(0);
            $table->decimal('stack_run', 10, 2);
            $table->string('container_size');
            $table->decimal('rate', 10, 2);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');

            $table->index('shipping_line_id');
            $table->index('cypa_id');
            $table->index('container_size');
            $table->index('is_active');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('rate_per_clients');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
