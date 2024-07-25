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
        Schema::create('card_stocks', function (Blueprint $table) {
            $table->id('card_stock_id')->primary();
            $table->integer('card_stock_product_id')->default(0)->index();
            $table->decimal('card_stock_in', 10, 4)->default(0)->index();
            $table->decimal('card_stock_out', 10, 4)->default(0)->index();
            $table->decimal('card_stock_actual', 10, 4)->default(0)->index(); //not use
            $table->decimal('card_stock_diff', 10, 4)->default(0)->index();
            $table->string('card_stock_diff_label', 20)->nullable();
            $table->decimal('card_stock_adjustment_total', 10, 4)->default(0)->index();
            $table->string('card_stock_adjustment_total_label', 20)->nullable();
            $table->decimal('card_stock_nominal', 15, 2)->default(0)->index();
            $table->string('card_stock_nominal_label', 20)->nullable(); //not use
            $table->string('card_stock_type', 20)->index(); // plus or minus
            $table->string('card_stock_status', 20)->index(); // in, out, adjustment
            $table->text('card_stock_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_stocks');
    }
};
