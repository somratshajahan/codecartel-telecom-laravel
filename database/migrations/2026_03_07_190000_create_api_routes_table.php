<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_routes')) {
            return;
        }

        Schema::create('api_routes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('module_type')->default('manual');
            $table->string('module_name');
            $table->unsignedBigInteger('api_id')->nullable();
            $table->string('service')->default('all');
            $table->string('code')->default('all');
            $table->unsignedInteger('priority')->default(1);
            $table->string('prefix')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_routes');
    }
};