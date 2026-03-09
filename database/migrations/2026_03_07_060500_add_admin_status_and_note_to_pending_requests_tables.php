<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('drive_requests')) {
            Schema::table('drive_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('drive_requests', 'admin_status')) {
                    $table->string('admin_status')->nullable()->after('status');
                }

                if (! Schema::hasColumn('drive_requests', 'admin_note')) {
                    $table->text('admin_note')->nullable()->after('admin_status');
                }
            });
        }

        if (Schema::hasTable('regular_requests')) {
            Schema::table('regular_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('regular_requests', 'admin_status')) {
                    $table->string('admin_status')->nullable()->after('status');
                }

                if (! Schema::hasColumn('regular_requests', 'admin_note')) {
                    $table->text('admin_note')->nullable()->after('admin_status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('drive_requests')) {
            Schema::table('drive_requests', function (Blueprint $table) {
                if (Schema::hasColumn('drive_requests', 'admin_note')) {
                    $table->dropColumn('admin_note');
                }

                if (Schema::hasColumn('drive_requests', 'admin_status')) {
                    $table->dropColumn('admin_status');
                }
            });
        }

        if (Schema::hasTable('regular_requests')) {
            Schema::table('regular_requests', function (Blueprint $table) {
                if (Schema::hasColumn('regular_requests', 'admin_note')) {
                    $table->dropColumn('admin_note');
                }

                if (Schema::hasColumn('regular_requests', 'admin_status')) {
                    $table->dropColumn('admin_status');
                }
            });
        }
    }
};