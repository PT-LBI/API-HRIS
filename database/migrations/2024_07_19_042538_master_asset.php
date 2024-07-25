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
        Schema::create('master_assets', function (Blueprint $table) {
            $table->id('asset_id')->primary();
            $table->string('asset_name');
            $table->string('asset_code');
            $table->string('asset_type');
            $table->string('asset_status', 20)->default('active');
            $table->text('asset_desc')->nullable();
            $table->text('asset_image')->nullable();
            $table->date('asset_purchase_date')->nullable();
            $table->decimal('asset_purchase_cost', 15, 2)->default(0);
            $table->decimal('asset_depreciation', 10, 2)->default(0);
            $table->integer('asset_depreciation_duration')->default(0);
            $table->decimal('asset_price_actual', 15, 2)->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_assets');
    }
};
