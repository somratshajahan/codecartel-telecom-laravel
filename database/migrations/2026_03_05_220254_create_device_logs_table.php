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
        Schema::create('device_logs', function (Blueprint $table) {
    $table->id();
    $table->string('ip_address');
    $table->string('username');
    $table->string('browser_os');
    $table->boolean('two_step_verified')->default(false);
    $table->enum('status', ['active', 'deactive'])->default('deactive');
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};
