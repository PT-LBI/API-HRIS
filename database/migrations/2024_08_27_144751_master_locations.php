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
        schema::create('master_locations', function (Blueprint $table) {
            $table->id('location_id')->primary();
            $table->string('location_name', 100)->index();
            $table->string('location_longitude');
            $table->string('location_latitude');
            $table->integer('location_radius')->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('user_location_id')->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_locations');
    }
};
