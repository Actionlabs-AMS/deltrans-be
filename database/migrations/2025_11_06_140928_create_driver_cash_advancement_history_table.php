<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('driver_cash_advancement_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->string('shift')->nullable();
            $table->unsignedBigInteger('driver_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');

            $table->index('transaction_date');
            $table->index('driver_id');
            $table->index('shift');
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
        
        Schema::dropIfExists('driver_cash_advancement_history');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
