<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            if (! Schema::hasColumn('apis', 'api_key')) {
                $table->string('api_key')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('apis', 'api_url')) {
                $table->string('api_url')->nullable()->after('provider');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('apis', 'api_key')) {
                $columnsToDrop[] = 'api_key';
            }

            if (Schema::hasColumn('apis', 'api_url')) {
                $columnsToDrop[] = 'api_url';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};