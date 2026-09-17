<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Replace bookings.is_ship_in with a free-text remarks column.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('bookings', 'remarks')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('remarks')->nullable()->after('is_complete');
            });
        }

        if (Schema::hasColumn('bookings', 'is_ship_in')) {
            DB::table('bookings')->where('is_ship_in', 1)->update(['remarks' => 'SHIP IN']);
            DB::table('bookings')->where('is_ship_in', 0)->update(['remarks' => 'SHIP OUT']);

            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex(['is_ship_in']);
                $table->dropColumn('is_ship_in');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('bookings', 'is_ship_in')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->boolean('is_ship_in')->default(true)->after('is_complete');
                $table->index('is_ship_in');
            });
        }

        if (Schema::hasColumn('bookings', 'remarks')) {
            DB::table('bookings')->update([
                'is_ship_in' => DB::raw("CASE WHEN remarks = 'SHIP OUT' THEN 0 ELSE 1 END"),
            ]);

            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('remarks');
            });
        }
    }
};
