<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('main_bal', 15, 2)->default(0)->after('parent_id');
            $table->decimal('bank_bal', 15, 2)->default(0)->after('main_bal');
            $table->decimal('drive_bal', 15, 2)->default(0)->after('bank_bal');
            $table->decimal('stock', 15, 2)->default(0)->after('drive_bal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['main_bal', 'bank_bal', 'drive_bal', 'stock']);
        });
    }
};
