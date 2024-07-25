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
        schema::create('po_details', function (Blueprint $table) {
            $table->id('po_detail_id')->primary();
            $table->integer('po_detail_po_id');
            $table->integer('po_detail_product_id');
            $table->string('po_detail_product_name', 100);
            $table->string('po_detail_product_sku', 50);
            $table->integer('po_detail_product_category_id');
            $table->string('po_detail_product_category_name', 100);
            $table->decimal('po_detail_qty', 10, 4)->default(0);
            $table->decimal('po_detail_product_purchase_price', 15, 2)->default(0);
            $table->decimal('po_detail_subtotal', 15, 2)->default(0);
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_details');
    }
};
