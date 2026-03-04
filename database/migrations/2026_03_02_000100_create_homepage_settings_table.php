<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_title')->nullable();
            $table->string('operators_title')->nullable();
            $table->text('operators_subtitle')->nullable();
            $table->string('features_title')->nullable();
            $table->text('features_subtitle')->nullable();
            $table->string('stats_customers_label')->nullable();
            $table->string('stats_customers_value')->nullable();
            $table->string('stats_recharged_label')->nullable();
            $table->string('stats_recharged_value')->nullable();
            $table->string('stats_operators_label')->nullable();
            $table->string('stats_operators_value')->nullable();
            $table->string('stats_service_label')->nullable();
            $table->string('stats_service_value')->nullable();
            $table->string('footer_company_name')->nullable();
            $table->text('footer_description')->nullable();
            $table->string('footer_address')->nullable();
            $table->string('footer_phone')->nullable();
            $table->string('footer_email')->nullable();
            $table->string('social_whatsapp_url')->nullable();
            $table->string('social_youtube_url')->nullable();
            $table->string('social_shopee_url')->nullable();
            $table->string('social_telegram_url')->nullable();
            $table->string('social_messenger_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_settings');
    }
};

