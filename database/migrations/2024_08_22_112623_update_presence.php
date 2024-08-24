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
        Schema::table('presence', function (Blueprint $table) {
            $table->string('presence_in_longitude')->nullable();
            $table->string('presence_in_latitude')->nullable();
            $table->string('presence_out_longitude')->nullable();
            $table->string('presence_out_latitude')->nullable();
            $table->text('presence_in_note')->nullable();
            $table->text('presence_out_note')->nullable();
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
