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

        Schema::create('shipping_lines', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('shipping_id');
            $table->string('shipping_line_name');
            $table->string('shipping_line_email_address');
            $table->text('shipping_line_address')->nullable();
            $table->string('shipping_line_contact_name')->nullable();
            $table->string('shipping_line_contact_mobile')->nullable();
            $table->string('shipping_line_landline_1')->nullable();
            $table->string('shipping_line_landline_2')->nullable();
            $table->string('shipping_line_landline_3')->nullable();
            $table->string('shipping_line_landline_4')->nullable();
            $table->string('shipping_line_faxno')->nullable();
            $table->string('shipping_line_tin')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('shipping_line_email_address');
            $table->index('shipping_line_name');
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
        
        Schema::dropIfExists('shipping_lines');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
