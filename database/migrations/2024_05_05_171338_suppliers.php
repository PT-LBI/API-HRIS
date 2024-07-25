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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id')->primary();
            $table->string('supplier_name', 100);
            $table->string('supplier_type', 50);
            $table->string('supplier_other_name', 100);
            $table->string('supplier_identity_number', 20);
            $table->string('supplier_npwp', 20);
            $table->string('supplier_phone_number', 13);
            $table->text('supplier_address');
            $table->integer('supplier_province_id')->default(0);
            $table->string('supplier_province_name', 100);
            $table->integer('supplier_district_id')->default(0);
            $table->string('supplier_district_name', 100);
            $table->string('supplier_status', 20);
            $table->text('supplier_image')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
