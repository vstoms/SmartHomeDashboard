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
        Schema::create('device_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('device_ids')->nullable(); // Array of Homey device IDs
            $table->json('settings')->nullable(); // Additional settings
            $table->integer('grid_x')->default(0);
            $table->integer('grid_y')->default(0);
            $table->integer('grid_w')->default(2);
            $table->integer('grid_h')->default(2);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_groups');
    }
};
