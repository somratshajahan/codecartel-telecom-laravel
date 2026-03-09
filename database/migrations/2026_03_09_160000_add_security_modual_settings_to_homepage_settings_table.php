<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_settings')) {
            return;
        }

        Schema::table('homepage_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('homepage_settings', 'security_ssl_https_redirect')) {
                $table->string('security_ssl_https_redirect', 20)->default('disable');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_admin_login_captcha')) {
                $table->string('security_admin_login_captcha', 20)->default('disable');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_reseller_login_captcha')) {
                $table->string('security_reseller_login_captcha', 20)->default('disable');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_pin_expire_days')) {
                $table->unsignedInteger('security_pin_expire_days')->default(100);
            }
            if (! Schema::hasColumn('homepage_settings', 'security_password_expire_days')) {
                $table->unsignedInteger('security_password_expire_days')->default(100);
            }
            if (! Schema::hasColumn('homepage_settings', 'security_password_strong')) {
                $table->string('security_password_strong', 10)->default('yes');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_minimum_pin_length')) {
                $table->unsignedInteger('security_minimum_pin_length')->default(4);
            }
            if (! Schema::hasColumn('homepage_settings', 'security_request_interval_minutes')) {
                $table->unsignedInteger('security_request_interval_minutes')->default(1);
            }
            if (! Schema::hasColumn('homepage_settings', 'security_session_timeout_minutes')) {
                $table->unsignedInteger('security_session_timeout_minutes')->default(20000);
            }
            if (! Schema::hasColumn('homepage_settings', 'security_support_ticket')) {
                $table->string('security_support_ticket', 20)->default('enable');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_send_otp_via')) {
                $table->string('security_send_otp_via', 30)->default('sms_modem');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_send_alert_via')) {
                $table->string('security_send_alert_via', 30)->default('sms_modem');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_send_offline_sms_via')) {
                $table->string('security_send_offline_sms_via', 30)->default('sms_modem');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_bulk_flexi_limit')) {
                $table->unsignedInteger('security_bulk_flexi_limit')->default(1000);
            }
            if (! Schema::hasColumn('homepage_settings', 'security_auto_sending_limit')) {
                $table->unsignedInteger('security_auto_sending_limit')->default(999);
            }
            if (! Schema::hasColumn('homepage_settings', 'security_reseller_overpayment_limit')) {
                $table->string('security_reseller_overpayment_limit', 10)->default('no');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_modem')) {
                $table->string('security_modem', 50)->default('modem_v1');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_daily_limit')) {
                $table->unsignedBigInteger('security_daily_limit')->default(5000000);
            }
            if (! Schema::hasColumn('homepage_settings', 'security_gp')) {
                $table->string('security_gp', 10)->default('off');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_robi')) {
                $table->string('security_robi', 10)->default('off');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_banglalink')) {
                $table->string('security_banglalink', 10)->default('off');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_airtel')) {
                $table->string('security_airtel', 10)->default('off');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_teletalk')) {
                $table->string('security_teletalk', 10)->default('off');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_skitto')) {
                $table->string('security_skitto', 10)->default('off');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_popup_notice')) {
                $table->string('security_popup_notice', 10)->default('on');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_sms_sent_system')) {
                $table->string('security_sms_sent_system', 30)->default('only_offline');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_bank_balance')) {
                $table->string('security_bank_balance', 10)->default('on');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_drive_balance')) {
                $table->string('security_drive_balance', 10)->default('off');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_balance_transfer')) {
                $table->string('security_balance_transfer', 10)->default('on');
            }
            if (! Schema::hasColumn('homepage_settings', 'security_commission_system')) {
                $table->string('security_commission_system', 30)->default('all_level');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_settings')) {
            return;
        }

        Schema::table('homepage_settings', function (Blueprint $table) {
            $columns = [
                'security_ssl_https_redirect',
                'security_admin_login_captcha',
                'security_reseller_login_captcha',
                'security_pin_expire_days',
                'security_password_expire_days',
                'security_password_strong',
                'security_minimum_pin_length',
                'security_request_interval_minutes',
                'security_session_timeout_minutes',
                'security_support_ticket',
                'security_send_otp_via',
                'security_send_alert_via',
                'security_send_offline_sms_via',
                'security_bulk_flexi_limit',
                'security_auto_sending_limit',
                'security_reseller_overpayment_limit',
                'security_modem',
                'security_daily_limit',
                'security_gp',
                'security_robi',
                'security_banglalink',
                'security_airtel',
                'security_teletalk',
                'security_skitto',
                'security_popup_notice',
                'security_sms_sent_system',
                'security_bank_balance',
                'security_drive_balance',
                'security_balance_transfer',
                'security_commission_system',
            ];

            $existingColumns = array_values(array_filter($columns, fn($column) => Schema::hasColumn('homepage_settings', $column)));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
