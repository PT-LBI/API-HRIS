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
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id')->primary();
            $table->string('email', 50)->nullable()->unique();
            $table->text('password')->nullable();
            $table->string('user_name', 100);
            $table->string('user_phone_number', 13)->nullable();
            $table->text('user_profile_url')->nullable();
            $table->string('user_role', 20);
            $table->string('user_status', 20);
            $table->string('user_position', 30)->nullable();
            $table->integer('user_province_id')->nullable();
            $table->string('user_province_name', 100)->nullable();
            $table->integer('user_district_id')->nullable();
            $table->string('user_district_name', 100)->nullable();
            $table->text('user_address')->nullable();
            $table->string('user_identity_number', 20)->nullable();
            $table->string('user_npwp', 20)->nullable();
            $table->text('user_desc')->nullable();
            $table->timestamp('user_join_date')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
