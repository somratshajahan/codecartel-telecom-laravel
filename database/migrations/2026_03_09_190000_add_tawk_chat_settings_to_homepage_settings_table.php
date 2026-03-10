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
            if (! Schema::hasColumn('homepage_settings', 'tawk_property_id')) {
                $table->text('tawk_property_id')->nullable();
            }

            if (! Schema::hasColumn('homepage_settings', 'tawk_widget_id')) {
                $table->text('tawk_widget_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_settings')) {
            return;
        }

        Schema::table('homepage_settings', function (Blueprint $table) {
            if (Schema::hasColumn('homepage_settings', 'tawk_property_id')) {
                $table->dropColumn('tawk_property_id');
            }

            if (Schema::hasColumn('homepage_settings', 'tawk_widget_id')) {
                $table->dropColumn('tawk_widget_id');
            }
        });
    }
};