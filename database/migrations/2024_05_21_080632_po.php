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
        schema::create('pos', function (Blueprint $table) {
            $table->id('po_id')->primary();
            $table->integer('po_supplier_id');
            $table->string('po_number', 50);
            $table->integer('po_pic_id');
            $table->datetime('po_date');
            $table->integer('po_total_product')->default(0);
            $table->decimal('po_total_product_qty', 10, 4)->default(0);
            $table->decimal('po_subtotal', 15, 2)->default(0);
            $table->decimal('po_tax', 15, 2)->default(0);
            $table->decimal('po_grandtotal', 15, 2)->default(0);
            $table->string('po_status', 20)->default('waiting');
            $table->string('po_status_receiving', 20)->default('waiting');
            $table->string('po_status_ship', 20);
            $table->integer('po_payment_bank_id')->default(0)->index();
            $table->string('po_payment_method', 20)->default('transfer');
            $table->string('po_payment_status', 20)->default('waiting');
            $table->decimal('po_config_tax', 10, 2)->default(0);
            $table->integer('po_ship_id')->default(0)->nullable();
            $table->integer('po_type')->default(0)->nullable();
            $table->decimal('po_tax_ppn', 15, 2)->default(0)->nullable();
            $table->decimal('po_config_tax_ppn', 10, 2)->default(0)->nullable();
            $table->text('po_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos');
    }
};
