<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('referral_code')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'referred_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('referred_by')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'referral_coin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('referral_coin')->default(0);
            });
        }

        if (! Schema::hasColumn('homepage_settings', 'referral_reward_coin')) {
            Schema::table('homepage_settings', function (Blueprint $table) {
                $table->unsignedInteger('referral_reward_coin')->default(0);
            });
        }

        if (! Schema::hasColumn('homepage_settings', 'referral_convert_coin')) {
            Schema::table('homepage_settings', function (Blueprint $table) {
                $table->unsignedInteger('referral_convert_coin')->default(0);
            });
        }

        if (! Schema::hasColumn('homepage_settings', 'referral_convert_amount')) {
            Schema::table('homepage_settings', function (Blueprint $table) {
                $table->decimal('referral_convert_amount', 15, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('homepage_settings', 'referral_convert_amount')) {
            Schema::table('homepage_settings', function (Blueprint $table) {
                $table->dropColumn('referral_convert_amount');
            });
        }

        if (Schema::hasColumn('homepage_settings', 'referral_convert_coin')) {
            Schema::table('homepage_settings', function (Blueprint $table) {
                $table->dropColumn('referral_convert_coin');
            });
        }

        if (Schema::hasColumn('homepage_settings', 'referral_reward_coin')) {
            Schema::table('homepage_settings', function (Blueprint $table) {
                $table->dropColumn('referral_reward_coin');
            });
        }

        if (Schema::hasColumn('users', 'referral_coin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('referral_coin');
            });
        }

        if (Schema::hasColumn('users', 'referred_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('referred_by');
            });
        }

        if (Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }
    }
};