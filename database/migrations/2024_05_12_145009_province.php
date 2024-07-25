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
        schema::create('provinces', function (Blueprint $table) {
            $table->id('provinces_id')->primary();
            $table->string('provinces_name', 100);
        });
        
        schema::create('districts', function (Blueprint $table) {
            $table->id('districts_id')->primary();
            $table->integer('districts_province_id');
            $table->string('districts_name', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('districts');
    }
};
