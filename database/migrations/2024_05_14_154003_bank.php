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
        schema::create('master_banks', function (Blueprint $table) {
            $table->id('bank_id')->primary();
            $table->string('bank_name', 20);
            $table->string('bank_account_name', 100);
            $table->string('bank_account_number', 20);
            $table->decimal('bank_current_balance',15, 2)->default(0);
            $table->decimal('bank_first_balance',15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_banks');
    }
};
