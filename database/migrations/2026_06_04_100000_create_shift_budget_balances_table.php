<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('shift_budget_balances', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->date('transaction_date');
            $table->string('shift', 16);
            $table->decimal('issued_budget', 15, 2)->default(0);
            $table->decimal('carried_from_previous', 15, 2)->default(0);
            $table->decimal('total_budget', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('remaining_coh', 15, 2)->default(0);
            $table->date('previous_shift_date')->nullable();
            $table->string('previous_shift', 16)->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['transaction_date', 'shift'], 'shift_budget_balances_date_shift_unique');
            $table->index('transaction_date');
            $table->index('shift');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        Schema::dropIfExists('shift_budget_balances');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
