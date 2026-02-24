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

        Schema::create('bookings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('reference_number')->unique()->nullable();
            $table->string('vessel')->nullable();
            $table->bigInteger('shipping_line_id')->unsigned();
            $table->bigInteger('cypa_id_from')->unsigned();
            $table->bigInteger('cypa_id_to')->unsigned();
            $table->date('expected_date')->nullable();
            $table->unsignedInteger('expected_container')->default(0);
            $table->boolean('is_complete')->default(false);
            $table->boolean('is_ship_in')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('cypa_id_from')->references('id')->on('cypa_details')->onDelete('cascade');
            $table->foreign('cypa_id_to')->references('id')->on('cypa_details')->onDelete('cascade');

            $table->index('reference_number');
            $table->index('shipping_line_id');
            $table->index('cypa_id_from');
            $table->index('cypa_id_to');
            $table->index('expected_date');
            $table->index('expected_container');
            $table->index('is_complete');
            $table->index('is_ship_in');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('bookings');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
