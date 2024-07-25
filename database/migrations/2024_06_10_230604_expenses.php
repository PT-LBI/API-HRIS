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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id('expenses_id')->primary();
            $table->string('expenses_number', 20)->unique()->index();
            $table->datetime('expenses_date');
            $table->integer('expenses_chart_account_id')->index();
            $table->integer('expenses_bank_id')->default(0)->index();
            $table->decimal('expenses_amount', 15, 0)->default(0)->index();
            $table->decimal('expenses_tax_ppn', 15, 2)->default(0)->nullable();
            $table->decimal('expenses_config_tax_ppn', 10, 2)->default(0)->nullable();
            $table->string('expenses_status')->default('waiting')->index();
            $table->string('expenses_type', 20)->default('normal')->nullable();
            $table->integer('expenses_ship_id')->default(0)->nullable();
            $table->text('expenses_image')->nullable();
            $table->text('expenses_note')->nullable();
            $table->integer('expenses_pic_id')->index();
            $table->integer('expenses_is_deleted')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
