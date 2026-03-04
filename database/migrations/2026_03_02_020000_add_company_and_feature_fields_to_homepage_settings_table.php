<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('page_title');
            $table->string('company_logo_url')->nullable()->after('company_name');

            $table->string('feature1_title')->nullable()->after('features_subtitle');
            $table->text('feature1_description')->nullable()->after('feature1_title');
            $table->string('feature2_title')->nullable()->after('feature1_description');
            $table->text('feature2_description')->nullable()->after('feature2_title');
            $table->string('feature3_title')->nullable()->after('feature2_description');
            $table->text('feature3_description')->nullable()->after('feature3_title');
            $table->string('feature4_title')->nullable()->after('feature3_description');
            $table->text('feature4_description')->nullable()->after('feature4_title');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_logo_url',
                'feature1_title',
                'feature1_description',
                'feature2_title',
                'feature2_description',
                'feature3_title',
                'feature3_description',
                'feature4_title',
                'feature4_description',
            ]);
        });
    }
};

