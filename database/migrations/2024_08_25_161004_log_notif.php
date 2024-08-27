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
        schema::create('log_notif', function (Blueprint $table) {
            $table->id('log_notif_id')->primary();
            $table->integer('log_notif_user_id')->index();
            $table->text('log_notif_data_json');
            $table->tinyInteger('log_notif_is_read')->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_notifs');
    }
};
