<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addRoutedColumns('flexi_requests', true);
        $this->addRoutedColumns('drive_requests', false);
        $this->addRoutedColumns('regular_requests', true);
    }

    public function down(): void
    {
        $this->dropRoutedColumns('flexi_requests', true);
        $this->dropRoutedColumns('drive_requests', false);
        $this->dropRoutedColumns('regular_requests', true);
    }

    protected function addRoutedColumns(string $table, bool $needsBalanceType): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table, $needsBalanceType) {
            if ($needsBalanceType && ! Schema::hasColumn($table, 'balance_type')) {
                $blueprint->string('balance_type')->nullable();
            }

            if (! Schema::hasColumn($table, 'is_routed')) {
                $blueprint->boolean('is_routed')->default(false);
            }

            if (! Schema::hasColumn($table, 'route_api_id')) {
                $blueprint->unsignedBigInteger('route_api_id')->nullable();
            }

            if (! Schema::hasColumn($table, 'remote_request_id')) {
                $blueprint->string('remote_request_id')->nullable();
            }

            if (! Schema::hasColumn($table, 'source_request_id')) {
                $blueprint->string('source_request_id')->nullable();
            }

            if (! Schema::hasColumn($table, 'source_request_type')) {
                $blueprint->string('source_request_type')->nullable();
            }

            if (! Schema::hasColumn($table, 'source_api_key')) {
                $blueprint->string('source_api_key')->nullable();
            }

            if (! Schema::hasColumn($table, 'source_callback_url')) {
                $blueprint->text('source_callback_url')->nullable();
            }

            if (! Schema::hasColumn($table, 'source_client_domain')) {
                $blueprint->string('source_client_domain')->nullable();
            }

            if (! Schema::hasColumn($table, 'charged_at')) {
                $blueprint->timestamp('charged_at')->nullable();
            }

            if (! Schema::hasColumn($table, 'settled_at')) {
                $blueprint->timestamp('settled_at')->nullable();
            }
        });
    }

    protected function dropRoutedColumns(string $table, bool $dropsBalanceType): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table, $dropsBalanceType) {
            $columns = [
                'is_routed',
                'route_api_id',
                'remote_request_id',
                'source_request_id',
                'source_request_type',
                'source_api_key',
                'source_callback_url',
                'source_client_domain',
                'charged_at',
                'settled_at',
            ];

            if ($dropsBalanceType) {
                $columns[] = 'balance_type';
            }

            $existing = array_values(array_filter($columns, fn(string $column) => Schema::hasColumn($table, $column)));

            if ($existing !== []) {
                $blueprint->dropColumn($existing);
            }
        });
    }
};