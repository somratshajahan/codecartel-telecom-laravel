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
            if (! Schema::hasColumn('homepage_settings', 'recaptcha_site_key')) {
                $table->text('recaptcha_site_key')->nullable();
            }

            if (! Schema::hasColumn('homepage_settings', 'recaptcha_secret_key')) {
                $table->text('recaptcha_secret_key')->nullable();
            }

            if (! Schema::hasColumn('homepage_settings', 'security_recaptcha')) {
                $table->string('security_recaptcha', 20)->default('disable');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_settings')) {
            return;
        }

        Schema::table('homepage_settings', function (Blueprint $table) {
            if (Schema::hasColumn('homepage_settings', 'recaptcha_site_key')) {
                $table->dropColumn('recaptcha_site_key');
            }

            if (Schema::hasColumn('homepage_settings', 'recaptcha_secret_key')) {
                $table->dropColumn('recaptcha_secret_key');
            }

            if (Schema::hasColumn('homepage_settings', 'security_recaptcha')) {
                $table->dropColumn('security_recaptcha');
            }
        });
    }
};
