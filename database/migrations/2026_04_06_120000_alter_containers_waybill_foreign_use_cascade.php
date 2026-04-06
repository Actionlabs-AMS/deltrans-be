<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Replace ON DELETE SET NULL with ON DELETE CASCADE so hard-deleting a waybill row does not
     * orphan containers as waybill_id = NULL (those rows incorrectly counted toward remaining).
     */
    public function up(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->dropForeign(['waybill_id']);
        });

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
        Schema::table('containers', function (Blueprint $table) {
            $table->dropForeign(['waybill_id']);
        });

        Schema::table('containers', function (Blueprint $table) {
            $table->foreign('waybill_id')
                ->references('id')
                ->on('waybill_details')
                ->nullOnDelete();
        });
    }
};
