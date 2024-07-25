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
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id('menu_id')->primary();
            $table->integer('menu_parent_id')->default(0)->index()->nullable();
            $table->string('menu_key', 100)->defaultTo(null);
            $table->string('menu_title', 100)->defaultTo(null);
            $table->string('menu_icon', 250)->defaultTo(null);
            $table->string('menu_role', 500)->defaultTo('')->index();
            $table->string('menu_status', 15)->defaultTo('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_menus');
    }
};
