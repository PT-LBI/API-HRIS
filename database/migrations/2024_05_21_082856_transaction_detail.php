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
        schema::create('transaction_details', function (Blueprint $table) {
            $table->id('transaction_detail_id')->primary();
            $table->integer('transaction_detail_transaction_id')->index();
            $table->integer('transaction_detail_product_id')->index();
            $table->string('transaction_detail_product_sku', 50);
            $table->string('transaction_detail_product_name', 100);
            $table->decimal('transaction_detail_qty', 10, 4)->default(0);
            $table->decimal('transaction_detail_price_unit', 15, 2)->default(0);
            $table->decimal('transaction_detail_total_price', 15, 2)->default(0);
            $table->decimal('transaction_detail_adjust_price', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
