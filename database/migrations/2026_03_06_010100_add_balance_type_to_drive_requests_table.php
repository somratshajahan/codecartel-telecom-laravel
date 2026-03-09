<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('drive_requests', 'balance_type')) {
            Schema::table('drive_requests', function (Blueprint $table) {
                $table->string('balance_type')->default('drive_bal')->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('drive_requests', 'balance_type')) {
            Schema::table('drive_requests', function (Blueprint $table) {
                $table->dropColumn('balance_type');
            });
        }
    }
};