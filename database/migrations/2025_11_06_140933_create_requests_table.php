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

        Schema::create('requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('request_id');
            $table->integer('extra_money')->default(0);
            $table->text('reason')->default('');
            $table->integer('type')->default(0);
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('requests');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};

