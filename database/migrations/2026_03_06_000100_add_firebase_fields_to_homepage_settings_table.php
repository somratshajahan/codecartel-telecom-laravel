<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->string('firebase_api_key')->nullable()->after('sms_api_url');
            $table->string('firebase_auth_domain')->nullable()->after('firebase_api_key');
            $table->string('firebase_project_id')->nullable()->after('firebase_auth_domain');
            $table->string('firebase_storage_bucket')->nullable()->after('firebase_project_id');
            $table->string('firebase_messaging_sender_id')->nullable()->after('firebase_storage_bucket');
            $table->string('firebase_app_id')->nullable()->after('firebase_messaging_sender_id');
            $table->text('firebase_vapid_key')->nullable()->after('firebase_app_id');
            $table->longText('firebase_service_account_json')->nullable()->after('firebase_vapid_key');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->dropColumn([
                'firebase_api_key',
                'firebase_auth_domain',
                'firebase_project_id',
                'firebase_storage_bucket',
                'firebase_messaging_sender_id',
                'firebase_app_id',
                'firebase_vapid_key',
                'firebase_service_account_json',
            ]);
        });
    }
};