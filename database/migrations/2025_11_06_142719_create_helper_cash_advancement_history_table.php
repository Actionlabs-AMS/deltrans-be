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

        Schema::create('helper_cash_advancement_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('hcah_id');
            $table->decimal('hcah_amount', 15, 2)->default(0);
            $table->date('hcah_transaction_date');
            $table->string('hcah_shift')->nullable();
            $table->bigInteger('helper_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('helper_id')->references('helper_id')->on('helpers')->onDelete('cascade');

            // Indexes
            $table->index('hcah_transaction_date');
            $table->index('helper_id');
            $table->index('hcah_shift');
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
        
        Schema::dropIfExists('helper_cash_advancement_history');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
