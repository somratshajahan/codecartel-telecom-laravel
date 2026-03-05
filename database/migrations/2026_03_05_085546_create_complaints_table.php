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
       Schema::create('complaints', function (Blueprint $table) {
        $table->id();
        $table->string('subject');
        $table->text('message');
        $table->string('sender_email');
        $table->string('status')->default('Open'); // Open, Answered, etc.
        $table->text('reply')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
