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
        Schema::create('config_tax', function (Blueprint $table) {
            $table->id('tax_id')->primary();
            $table->decimal('tax_value', 10, 2)->default(0)->index();
            $table->string('tax_type')->default('nominal');
            $table->decimal('tax_ppn_value', 10, 2)->default(0)->index();
            $table->string('tax_ppn_type')->default('nominal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_tax');
    }
};
