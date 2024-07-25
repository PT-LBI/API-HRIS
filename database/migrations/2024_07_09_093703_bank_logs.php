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
        Schema::create('bank_logs', function (Blueprint $table) {
            $table->id('log_id')->primary();
            $table->integer('log_bank_id')->default(0)->index();
            $table->integer('log_po_id')->default(0)->index();
            $table->integer('log_transaction_id')->default(0)->index();
            $table->integer('log_expenses_id')->default(0)->index();
            $table->decimal('log_amount', 15, 2)->default(0)->index();
            $table->string('log_type', 30)->nullable();
            $table->string('log_note', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_logs');
    }
};
