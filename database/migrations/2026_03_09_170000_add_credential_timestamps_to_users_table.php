<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'password_changed_at') || ! Schema::hasColumn('users', 'pin_changed_at')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'password_changed_at')) {
                    $table->timestamp('password_changed_at')->nullable()->after('password');
                }

                if (! Schema::hasColumn('users', 'pin_changed_at')) {
                    $table->timestamp('pin_changed_at')->nullable()->after('pin');
                }
            });
        }

        if (Schema::hasColumn('users', 'password_changed_at')) {
            DB::table('users')
                ->whereNull('password_changed_at')
                ->update(['password_changed_at' => DB::raw('COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)')]);
        }

        if (Schema::hasColumn('users', 'pin_changed_at')) {
            DB::table('users')
                ->whereNull('pin_changed_at')
                ->update(['pin_changed_at' => DB::raw('COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }

            if (Schema::hasColumn('users', 'pin_changed_at')) {
                $table->dropColumn('pin_changed_at');
            }
        });
    }
};