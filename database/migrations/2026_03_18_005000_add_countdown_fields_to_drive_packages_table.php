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
        Schema::table('drive_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('drive_packages', 'countdown_minutes')) {
                $table->unsignedInteger('countdown_minutes')->nullable()->after('expire');
            }

            if (! Schema::hasColumn('drive_packages', 'offer_ends_at')) {
                $table->dateTime('offer_ends_at')->nullable()->after('countdown_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drive_packages', function (Blueprint $table) {
            if (Schema::hasColumn('drive_packages', 'offer_ends_at')) {
                $table->dropColumn('offer_ends_at');
            }

            if (Schema::hasColumn('drive_packages', 'countdown_minutes')) {
                $table->dropColumn('countdown_minutes');
            }
        });
    }
};
