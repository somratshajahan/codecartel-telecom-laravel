<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSetting extends Model
{
    protected $fillable = [
        'page_title',
        'company_name',
        'company_logo_url',
        'favicon_path',
        'operators_title',
        'operators_subtitle',
        'features_title',
        'features_subtitle',
        'feature1_title',
        'feature1_description',
        'feature2_title',
        'feature2_description',
        'feature3_title',
        'feature3_description',
        'feature4_title',
        'feature4_description',
        'stats_customers_label',
        'stats_customers_value',
        'stats_recharged_label',
        'stats_recharged_value',
        'stats_operators_label',
        'stats_operators_value',
        'stats_service_label',
        'stats_service_value',
        'footer_company_name',
        'footer_description',
        'footer_address',
        'footer_phone',
        'footer_email',
        'social_whatsapp_url',
        'social_youtube_url',
        'social_shopee_url',
        'social_telegram_url',
        'social_messenger_url',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'sms_api_key',
        'sms_sender_id',
        'sms_api_url',
        'firebase_api_key',
        'firebase_auth_domain',
        'firebase_project_id',
        'firebase_storage_bucket',
        'firebase_messaging_sender_id',
        'firebase_app_id',
        'firebase_vapid_key',
        'firebase_service_account_json',
        'google_otp_enabled',
        'google_otp_issuer',
    ];

    protected $casts = [
        'google_otp_enabled' => 'boolean',
    ];
}
