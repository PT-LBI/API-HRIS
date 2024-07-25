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
        schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id')->primary();
            $table->datetime('transaction_date');
            $table->string('transaction_number', 50);
            $table->integer('transaction_customer_id')->index();
            $table->integer('transaction_pic_id')->index();
            $table->string('transaction_pic_name', 100);
            $table->integer('transaction_total_product')->default(0);
            $table->decimal('transaction_total_product_qty', 10, 4)->default(0);
            $table->decimal('transaction_subtotal', 15, 2)->default(0);
            $table->decimal('transaction_tax', 15, 2)->default(0)->nullable();
            $table->decimal('transaction_config_tax', 10, 2)->default(0)->nullable();
            $table->decimal('transaction_tax_ppn', 15, 2)->default(0)->nullable();
            $table->decimal('transaction_config_tax_ppn', 10, 2)->default(0)->nullable();
            $table->string('transaction_disc_type', 20)->nullable();
            $table->decimal('transaction_disc_percent', 15, 2)->default(0);
            $table->decimal('transaction_disc_nominal', 15, 2)->default(0);
            $table->decimal('transaction_shipping_cost', 15, 2)->default(0);
            $table->decimal('transaction_grandtotal', 15, 2)->default(0);
            $table->string('transaction_status', 20)->default('waiting');
            $table->string('transaction_status_delivery', 20)->default('waiting');
            $table->string('transaction_payment_method', 20)->default('transfer');
            $table->string('transaction_payment_status', 20)->default('waiting');
            $table->integer('transaction_travel_doc')->default(0);
            $table->integer('transaction_payment_bank_id')->default(0)->index();
            $table->text('transaction_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
