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
        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission', 10, 2);
            $table->date('expire');
            $table->string('status')->default('active');
            $table->integer('sell_today')->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('comm', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regular_packages');
    }
};
