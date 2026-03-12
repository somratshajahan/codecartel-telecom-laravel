<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apis') || Schema::hasColumn('apis', 'client_domain')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            $table->string('client_domain')->nullable()->after('api_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('apis') || ! Schema::hasColumn('apis', 'client_domain')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            $table->dropColumn('client_domain');
        });
    }
};