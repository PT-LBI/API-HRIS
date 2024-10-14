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
        schema::create('leave', function (Blueprint $table) {
            $table->id('leave_id')->primary();
            $table->integer('leave_user_id')->index();
            $table->string('leave_type', 20)->default('leave')->index();
            $table->date('leave_start_date')->index();
            $table->date('leave_end_date')->nullable()->index();
            $table->integer('leave_day')->default(0);
            $table->text('leave_desc')->nullable();
            $table->text('leave_image')->nullable();
            $table->string('leave_status', 20)->default('waiting')->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave');
    }
};
