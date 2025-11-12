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

        Schema::create('billing_statements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('bs_transaction_number')->primary();
            $table->date('bs_transaction_date');
            $table->text('bs_description_of_charges')->nullable();
            $table->string('bs_container_size')->nullable();
            $table->decimal('bs_rate_of_trip', 15, 2)->default(0);
            $table->decimal('bs_total_amount', 15, 2)->default(0);
            $table->string('soa_transaction_number')->nullable();
            $table->string('waybill_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('soa_transaction_number')->references('soa_transaction_number')->on('statement_of_accounts')->onDelete('set null');
            $table->foreign('waybill_number')->references('waybill_number')->on('waybill_details')->onDelete('set null');

            // Indexes
            $table->index('bs_transaction_date');
            $table->index('soa_transaction_number');
            $table->index('waybill_number');
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
        
        Schema::dropIfExists('billing_statements');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
