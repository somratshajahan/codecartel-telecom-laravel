<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sslcommerz_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('tran_id')->unique();
            $table->string('session_key')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('BDT');
            $table->string('status')->default('initiated');
            $table->string('gateway_status')->nullable();
            $table->text('gateway_url')->nullable();
            $table->decimal('validated_amount', 10, 2)->nullable();
            $table->string('bank_tran_id')->nullable();
            $table->string('card_type')->nullable();
            $table->decimal('store_amount', 10, 2)->nullable();
            $table->string('validation_id')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('init_response_payload')->nullable();
            $table->longText('validation_payload')->nullable();
            $table->longText('callback_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sslcommerz_transactions');
    }
};