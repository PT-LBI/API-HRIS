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
        Schema::create('master_payrolls', function (Blueprint $table) {
            $table->id('payroll_id')->primary();
            $table->integer('payroll_user_id')->index();
            $table->decimal('payroll_value', 12, 2)->default(0);
            $table->decimal('payroll_overtime_hour', 10, 2)->default(0);
            $table->decimal('payroll_transport', 10, 2)->default(0);
            $table->decimal('payroll_communication', 10, 2)->default(0);
            $table->decimal('payroll_absenteeism_cut', 10, 2)->default(0);
            $table->decimal('payroll_bpjs', 10, 2)->default(0);
            $table->string('payroll_status', 20)->default('active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('user_payrolls', function (Blueprint $table) {
            $table->id('user_payroll_id')->primary();
            $table->integer('user_payroll_user_id')->index();
            $table->integer('user_payroll_payroll_id')->index();
            $table->decimal('user_payroll_value', 12, 2)->default(0);
            $table->decimal('user_payroll_overtime_hour_total', 4, 2)->default(0);
            $table->decimal('user_payroll_overtime_hour', 10, 2)->default(0);
            $table->decimal('user_payroll_transport', 10, 2)->default(0);
            $table->decimal('user_payroll_communication', 10, 2)->default(0);
            $table->decimal('user_payroll_absenteeism_cut_total', 4, 2)->default(0);
            $table->decimal('user_payroll_absenteeism_cut', 10, 2)->default(0);
            $table->decimal('user_payroll_bpjs_kes', 10, 2)->default(0);
            $table->decimal('user_payroll_bpjs_tk', 10, 2)->default(0);
            $table->decimal('user_payroll_total_accepted', 12, 2)->default(0);
            $table->string('user_payroll_status', 20)->default('unpaid');
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
        Schema::dropIfExists('master_payrolls');
        Schema::dropIfExists('user_payrolls');
    }
};
