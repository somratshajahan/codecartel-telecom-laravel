<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->boolean('google_otp_enabled')->default(false)->after('firebase_service_account_json');
            $table->string('google_otp_issuer')->nullable()->after('google_otp_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->dropColumn(['google_otp_enabled', 'google_otp_issuer']);
        });
    }
};