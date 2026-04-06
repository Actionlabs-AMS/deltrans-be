<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Every container must belong to a waybill (product flow: booking → waybill → container).
     * Drops orphan rows with no waybill, then enforces NOT NULL on MySQL (FK must be dropped first).
     */
    public function up(): void
    {
        DB::table('containers')->whereNull('waybill_id')->delete();

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('containers', function (Blueprint $table) {
            $table->dropForeign(['waybill_id']);
        });

        DB::statement('ALTER TABLE containers MODIFY waybill_id BIGINT UNSIGNED NOT NULL');

        Schema::table('containers', function (Blueprint $table) {
            $table->foreign('waybill_id')
                ->references('id')
                ->on('waybill_details')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('containers', function (Blueprint $table) {
            $table->dropForeign(['waybill_id']);
        });

        DB::statement('ALTER TABLE containers MODIFY waybill_id BIGINT UNSIGNED NULL');

        Schema::table('containers', function (Blueprint $table) {
            $table->foreign('waybill_id')
                ->references('id')
                ->on('waybill_details')
                ->cascadeOnDelete();
        });
    }
};
