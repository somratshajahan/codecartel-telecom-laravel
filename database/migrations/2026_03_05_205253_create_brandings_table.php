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
Schema::create('brandings', function (Blueprint $table) {
    $table->id();
    
    // Basic Info
    $table->string('brand_name')->nullable();
    $table->string('top_title')->nullable();
    $table->string('footer')->nullable();
    
    // System Status (On/Off/Auto/Manual)
    $table->string('registration_system')->default('off'); // Self Registration
    $table->string('drive_system')->default('manual');     // Auto/Manual
    $table->string('regular_pack')->default('manual');     // Auto/Manual
    $table->string('modem_connection')->default('lock');   // Unlock/Lock
    $table->string('modem_pass')->nullable();
    
    // Request & List Settings
    $table->string('request_success_type')->default('normal'); // Normal/Quick
    $table->text('success_list')->nullable();
    $table->text('cancel_list')->nullable();
    
    // SMS Gateway Info
    $table->string('sms_provider')->nullable(); // Solutionsclan, bulksmsbd, etc.
    $table->string('sms_user')->nullable();
    $table->string('sms_password')->nullable();
    
    // Payment Numbers
    $table->string('bkash')->nullable();
    $table->string('rocket')->nullable();
    $table->string('nagad')->nullable();
    $table->string('upay')->nullable();
    
    // Social & Links
    $table->string('alert_no')->nullable();
    $table->string('whatsapp_link')->nullable();
    $table->string('telegram_link')->nullable();
    $table->string('youtube_channel')->nullable();
    $table->string('shopping_link')->nullable();
    
    // Sliders / Image Links
    $table->string('image_1_link')->nullable();
    $table->string('image_2_link')->nullable();
    $table->string('image_3_link')->nullable();
    $table->string('image_4_link')->nullable();
    
    // SEO Settings
    $table->text('meta_description')->nullable();
    $table->text('keywords')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brandings');
    }
};
