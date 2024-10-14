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
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code', 10)->index();
            $table->text('user_identity_address')->nullable();
            $table->integer('user_company_id')->index()->default(0);
            $table->integer('user_division_id')->index()->default(0);
            $table->string('user_bpjs_kes', 20)->nullable();
            $table->string('user_bpjs_tk', 20)->nullable();
            $table->string('user_place_birth', 30)->index()->nullable();
            $table->date('user_date_birth')->nullable();
            $table->text('user_education_json')->nullable();
            $table->string('user_marital_status', 20)->default('single');
            $table->integer('user_number_children')->default(0);
            $table->string('user_emergency_contact', 13)->nullable();
            $table->integer('user_entry_year')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
