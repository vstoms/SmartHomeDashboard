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
        Schema::table('homey_settings', function (Blueprint $table) {
            $table->string('name')->after('id')->default('My Homey');
            $table->string('ip_address')->after('name');
            $table->dropColumn(['cloud_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homey_settings', function (Blueprint $table) {
            $table->string('cloud_id')->after('id');
            $table->string('client_id')->after('cloud_id');
            $table->dropColumn(['name', 'ip_address']);
        });
    }
};
