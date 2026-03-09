<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('flexi_requests')) {
            return;
        }

        Schema::create('flexi_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('operator');
            $table->string('mobile', 11);
            $table->decimal('amount', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->string('type')->default('Prepaid');
            $table->string('trnx_id')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flexi_requests');
    }
};
