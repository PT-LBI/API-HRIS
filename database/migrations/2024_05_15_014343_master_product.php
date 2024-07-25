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
        Schema::create('master_products', function (Blueprint $table) {
            $table->id('product_id')->primary();
            $table->string('product_name', 100);
            $table->string('product_sku', 50);
            $table->integer('product_category_id')->default(0);
            $table->decimal('product_sell_price', 15, 2)->default(0);
            $table->string('product_unit', 20)->nullable();
            $table->decimal('product_stock', 10, 4)->default(0);
            $table->string('product_status')->default('active');
            $table->timestamp('product_price_updated_at')->nullable();
            $table->decimal('product_hpp', 15, 2)->default(0);
            $table->integer('product_show_hpp')->default(0);
            $table->text('product_image')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_products');
    }
};
