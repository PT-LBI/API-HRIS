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
        schema::create('po_payments', function (Blueprint $table) {
            $table->id('po_payment_id')->primary();
            $table->integer('po_payment_po_id')->index();
            $table->string('po_payment_method', 20)->default('transfer');
            $table->string('po_payment_status', 20)->default('waiting');
            $table->decimal('po_payment_amount', 15, 2)->default(0);
            $table->decimal('po_payment_installment', 15, 2)->default(0);
            $table->decimal('po_payment_remaining', 15, 2)->default(0);
            $table->integer('po_payment_bank_id')->index();
            $table->text('po_payment_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_payments');
    }
};
