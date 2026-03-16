<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::create('diesel_expenses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('vishner_or')->nullable();
            $table->string('vishner_dr')->nullable();
            $table->timestamps();
            $table->index('amount');
        });

        Schema::table('waybill_details', function (Blueprint $table) {
            $table->foreign('truck_trip_expense_id')
                ->references('id')
                ->on('truck_trip_expense')
                ->onDelete('set null');

            $table->foreign('diesel_expense_id')
                ->references('id')
                ->on('diesel_expenses')
                ->onDelete('set null');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::table('waybill_details', function (Blueprint $table) {
            $table->dropForeign(['truck_trip_expense_id']);
            $table->dropForeign(['diesel_expense_id']);
        });

        Schema::dropIfExists('diesel_expenses');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
