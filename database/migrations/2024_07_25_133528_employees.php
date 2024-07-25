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
        Schema::create('employees', function (Blueprint $table) {
            $table->id('employee_id')->primary();
            $table->string('employee_code', 10)->index();
            $table->string('employee_name', 100)->index();
            $table->text('employee_address')->nullable();
            $table->text('employee_identity_address')->nullable();
            $table->string('employee_identity_number', 20)->nullable();
            $table->string('employee_npwp', 20)->nullable();
            $table->string('employee_bpjs_kes', 20)->nullable();
            $table->string('employee_bpjs_tk', 20)->nullable();
            $table->string('employee_place_birth', 30)->nullable();
            $table->text('employee_education_json')->nullable();
            $table->string('employee_marital_status', 20)->default('single');
            $table->integer('employee_number_children')->default(0);
            $table->string('employee_emergency_contact', 13)->nullable();
            $table->integer('employee_entry_year')->nullable();
            $table->string('employee_position', 20)->nullable();
            $table->string('employee_status', 20)->default('active');
            $table->text('employee_image')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
