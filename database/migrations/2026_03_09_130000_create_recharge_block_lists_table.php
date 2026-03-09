<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharge_block_lists', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('operator', 20);
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['service', 'operator', 'amount'], 'recharge_block_lists_unique_entry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_block_lists');
    }
};