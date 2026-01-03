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
        Schema::table('dashboard_items', function (Blueprint $table) {
            $table->integer('grid_x')->default(0)->after('settings');
            $table->integer('grid_y')->default(0)->after('grid_x');
            $table->integer('grid_w')->default(1)->after('grid_y');
            $table->integer('grid_h')->default(1)->after('grid_w');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard_items', function (Blueprint $table) {
            $table->dropColumn(['grid_x', 'grid_y', 'grid_w', 'grid_h']);
        });
    }
};
