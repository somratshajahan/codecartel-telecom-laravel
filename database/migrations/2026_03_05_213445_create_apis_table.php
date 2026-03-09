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
       Schema::create('apis', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('user_id');
        $table->string('provider');
        $table->string('status')->default('active');
        $table->decimal('balance', 15, 2)->default(0.00);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apis');
    }
};
