<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('brandings', 'drive_balance')) {
            Schema::table('brandings', function (Blueprint $table) {
                $table->string('drive_balance')->default('on')->after('drive_system');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('brandings', 'drive_balance')) {
            Schema::table('brandings', function (Blueprint $table) {
                $table->dropColumn('drive_balance');
            });
        }
    }
};