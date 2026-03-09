<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->string('sslcommerz_store_id')->nullable()->after('upay');
            $table->string('sslcommerz_store_password')->nullable()->after('sslcommerz_store_id');
            $table->string('sslcommerz_mode')->nullable()->after('sslcommerz_store_password');
            $table->string('amarpay_store_id')->nullable()->after('sslcommerz_mode');
            $table->string('amarpay_signature_key')->nullable()->after('amarpay_store_id');
            $table->string('amarpay_mode')->nullable()->after('amarpay_signature_key');
        });
    }

    public function down(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->dropColumn([
                'sslcommerz_store_id',
                'sslcommerz_store_password',
                'sslcommerz_mode',
                'amarpay_store_id',
                'amarpay_signature_key',
                'amarpay_mode',
            ]);
        });
    }
};