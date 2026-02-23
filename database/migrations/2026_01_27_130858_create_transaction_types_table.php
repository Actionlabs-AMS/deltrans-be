<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->tinyInteger('type')->primary()->comment('Transaction type code: 0-4');
            $table->string('name')->unique()->comment('Human-readable transaction type name');
            $table->string('detail_table')->comment('Name of the detail table for this transaction type');
            $table->text('description')->nullable()->comment('Additional description of the transaction type');
            $table->boolean('is_active')->default(true)->comment('Whether this transaction type is active');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active', 'idx_is_active');
        });

        // Insert default transaction types
        DB::table('transaction_types')->insert([
            [
                'type' => 0,
                'name' => 'Add Budget',
                'detail_table' => 'issued_budget',
                'description' => 'Records when budget is added or issued to the system',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 1,
                'name' => 'Truck Trip Expense',
                'detail_table' => 'truck_trip_expense',
                'description' => 'Tracks expenses related to truck trips, including helper assignments and cash management',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 2,
                'name' => 'Parts Expense',
                'detail_table' => 'parts_expense',
                'description' => 'Records expenses for vehicle parts and maintenance items',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 3,
                'name' => 'Funds for Stack Run',
                'detail_table' => 'funds_for_stack_run',
                'description' => 'Tracks funds allocated for stack run operations',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 4,
                'name' => 'Advance Expense',
                'detail_table' => 'driver_cash_advancement_history',
                'description' => 'Cash advance: use driver_cash_advancement_history when type=1 (driver), helper_cash_advancement_history when type=2 (helper). Fetch by joining both tables.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_types');
    }
};
