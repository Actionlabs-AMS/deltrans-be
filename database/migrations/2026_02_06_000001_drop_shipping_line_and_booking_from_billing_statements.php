<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Drops shipping_line_id and booking_id from billing_statements (derived via statement_of_account_id).
     * No-op if columns were never created (e.g. fresh migrate with updated create table).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('billing_statements', 'shipping_line_id')) {
            return;
        }

        Schema::table('billing_statements', function (Blueprint $table) {
            $table->dropForeign(['shipping_line_id']);
            $table->dropForeign(['booking_id']);
            $table->dropColumn(['shipping_line_id', 'booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->bigInteger('shipping_line_id')->unsigned()->after('statement_of_account_id');
            $table->bigInteger('booking_id')->unsigned()->after('shipping_line_id');
            $table->foreign('shipping_line_id')->references('id')->on('shipping_lines')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->index('shipping_line_id');
            $table->index('booking_id');
        });
    }
};
