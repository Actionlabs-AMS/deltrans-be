<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: budget_history disabled for now – table creation commented out.
     */
    public function up(): void
    {
        // Disabled for now – budget_history not needed
        // DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        // Schema::create('budget_history', function (Blueprint $table) {
        //     $table->engine = 'InnoDB';
        //     $table->bigIncrements('id');
        //     $table->string('shift')->nullable();
        //     $table->decimal('total_amount', 15, 2)->default(0);
        //     $table->text('description')->nullable();
        //     $table->date('tracked_date')->nullable();
        //     $table->decimal('total_spent', 15, 2)->default(0);
        //     $table->string('expense_type')->nullable();
        //     $table->timestamps();
        //     $table->softDeletes();
        //     $table->index('shift');
        //     $table->index('tracked_date');
        //     $table->index('expense_type');
        // });
        // DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disabled for now – budget_history not needed
        // DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        // Schema::dropIfExists('budget_history');
        // DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
