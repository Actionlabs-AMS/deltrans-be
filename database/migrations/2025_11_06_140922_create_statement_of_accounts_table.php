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

        Schema::create('statement_of_accounts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('transaction_number')->primary();
            $table->date('transaction_date');
            $table->string('container_chassis_number')->nullable();
            $table->text('remarks')->nullable();
            $table->string('container_size')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('vat', 15, 2)->default(0);
            $table->string('truck_plate_number')->nullable();
            $table->string('waybill_number')->nullable();
            $table->bigInteger('origin_cypa_id')->nullable()->unsigned();
            $table->bigInteger('destination_cypa_id')->nullable()->unsigned();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('truck_plate_number')->references('plate_number')->on('fleet_trucks')->onDelete('set null');
            $table->foreign('waybill_number')->references('waybill_number')->on('waybill_details')->onDelete('set null');
            $table->foreign('origin_cypa_id')->references('id')->on('cypa_details')->onDelete('set null');
            $table->foreign('destination_cypa_id')->references('id')->on('cypa_details')->onDelete('set null');

            // Indexes
            $table->index('transaction_date');
            $table->index('truck_plate_number');
            $table->index('waybill_number');
            $table->index('origin_cypa_id');
            $table->index('destination_cypa_id');
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
        
        Schema::dropIfExists('statement_of_accounts');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
