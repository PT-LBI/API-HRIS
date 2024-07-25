<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cust_product_logs', function (Blueprint $table) {
            $table->id('cust_product_log_id')->primary();
            $table->integer('cust_product_id')->default(0);
            $table->integer('cust_product_product_id')->default(0);
            $table->decimal('cust_product_weight', 10, 4)->default(0);
            $table->decimal('cust_product_pack', 10, 4)->default(0);
            $table->string('cust_product_status')->default('active');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cust_product_logs');
    }
};
