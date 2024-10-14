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
        Schema::create('presence', function (Blueprint $table) {
            $table->id('presence_id')->primary();
            $table->integer('presence_user_id')->index();
            $table->integer('presence_schedule_id')->index();
            $table->timestamp('presence_in_time')->nullable();
            $table->text('presence_in_photo')->nullable();
            $table->timestamp('presence_out_time')->nullable();
            $table->text('presence_out_photo')->nullable();
            $table->time('presence_extra_time')->nullable();
            $table->string('presence_status', 20)->nullable();
            $table->decimal('presence_max_distance', 2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id('shift_id')->primary();
            $table->string('shift_name', 50)->index();
            $table->time('shift_start_time');
            $table->time('shift_finish_time');
            $table->string('shift_status')->default('active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id('schedule_id')->primary();
            $table->integer('schedule_shift_id')->index();
            $table->integer('schedule_user_id')->index();
            $table->date('schedule_date')->index();
            $table->text('schedule_note')->nullable();
            $table->string('schedule_status')->default('active');
            $table->integer('schedule_leave_id')->default(0)->index();
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
        Schema::dropIfExists('presence');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('schedules');
    }
};
