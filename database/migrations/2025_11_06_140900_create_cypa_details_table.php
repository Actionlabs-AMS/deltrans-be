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

        Schema::create('cypa_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_mobile')->nullable();
            $table->json('landlines')->nullable();
            $table->enum('location_type', ['Container Yard', 'Port Area'])->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('location_type');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('cypa_details');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
