<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apis')) {
            Schema::table('apis', function (Blueprint $table) {
                if (! Schema::hasColumn('apis', 'main_balance')) {
                    $table->decimal('main_balance', 15, 2)->nullable()->after('balance');
                }

                if (! Schema::hasColumn('apis', 'drive_balance')) {
                    $table->decimal('drive_balance', 15, 2)->nullable()->after('main_balance');
                }

                if (! Schema::hasColumn('apis', 'bank_balance')) {
                    $table->decimal('bank_balance', 15, 2)->nullable()->after('drive_balance');
                }
            });
        }

        if (! Schema::hasTable('api_connection_approvals')) {
            Schema::create('api_connection_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('api_id')->unique();
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('api_connection_approvals')) {
            Schema::dropIfExists('api_connection_approvals');
        }

        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach (['main_balance', 'drive_balance', 'bank_balance'] as $column) {
                if (Schema::hasColumn('apis', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};