<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('google_otp_secret')->nullable()->after('fcm_token_updated_at');
            $table->boolean('google_otp_enabled')->default(false)->after('google_otp_secret');
            $table->timestamp('google_otp_confirmed_at')->nullable()->after('google_otp_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_otp_secret', 'google_otp_enabled', 'google_otp_confirmed_at']);
        });
    }
};