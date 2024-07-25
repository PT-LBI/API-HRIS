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
        schema::create('transaction_payments', function (Blueprint $table) {
            $table->id('transaction_payment_id')->primary();
            $table->integer('transaction_payment_transaction_id')->index();
            $table->integer('transaction_payment_bank_id')->index();
            $table->string('transaction_payment_method', 20)->default('transfer');
            $table->string('transaction_payment_status', 20)->default('waiting');
            $table->decimal('transaction_payment_amount', 15, 2)->default(0);
            $table->text('transaction_payment_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_payments');
    }
};
