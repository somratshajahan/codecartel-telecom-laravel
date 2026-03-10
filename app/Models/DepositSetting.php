<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DepositSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'runtime_level',
        'level_name',
        'sort_order',
        'bkash_main',
        'rocket_main',
        'nagad_main',
        'upay_main',
        'account_price',
        'self_account_price',
        'bkash_bank',
        'rocket_bank',
        'nagad_bank',
        'upay_bank',
        'bkash_drive',
        'rocket_drive',
        'nagad_drive',
        'upay_drive',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'bkash_main' => 'decimal:2',
        'rocket_main' => 'decimal:2',
        'nagad_main' => 'decimal:2',
        'upay_main' => 'decimal:2',
        'account_price' => 'decimal:2',
        'self_account_price' => 'decimal:2',
        'bkash_bank' => 'decimal:2',
        'rocket_bank' => 'decimal:2',
        'nagad_bank' => 'decimal:2',
        'upay_bank' => 'decimal:2',
        'bkash_drive' => 'decimal:2',
        'rocket_drive' => 'decimal:2',
        'nagad_drive' => 'decimal:2',
        'upay_drive' => 'decimal:2',
    ];

    public static function editableColumns(): array
    {
        return [
            'bkash_main',
            'rocket_main',
            'nagad_main',
            'upay_main',
            'account_price',
            'self_account_price',
            'bkash_bank',
            'rocket_bank',
            'nagad_bank',
            'upay_bank',
            'bkash_drive',
            'rocket_drive',
            'nagad_drive',
            'upay_drive',
        ];
    }

    public static function defaultRows(): array
    {
        return [
            ['level' => 'subadmin', 'runtime_level' => 'subadmin', 'level_name' => 'subadmin', 'sort_order' => 1, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0],
            ['level' => 'reseller5', 'runtime_level' => 'house', 'level_name' => 'HOUSE', 'sort_order' => 2, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0],
            ['level' => 'reseller4', 'runtime_level' => 'dgm', 'level_name' => 'DGM', 'sort_order' => 3, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0],
            ['level' => 'reseller3', 'runtime_level' => 'dealer', 'level_name' => 'Dealer', 'sort_order' => 4, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0],
            ['level' => 'reseller2', 'runtime_level' => 'seller', 'level_name' => 'Seller', 'sort_order' => 5, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0],
            ['level' => 'reseller1', 'runtime_level' => 'retailer', 'level_name' => 'Retailer', 'sort_order' => 6, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0],
        ];
    }

    public static function rowsForDisplay(): array
    {
        $defaults = collect(static::defaultRows())->keyBy('level');

        if (! Schema::hasTable((new static())->getTable())) {
            return $defaults->values()->all();
        }

        $storedRows = static::query()->orderBy('sort_order')->get()->keyBy('level');

        return $defaults->map(function (array $defaultRow, string $level) use ($storedRows) {
            $storedRow = $storedRows->get($level);

            if (! $storedRow) {
                return $defaultRow;
            }

            return array_merge($defaultRow, array_filter(
                $storedRow->only(static::persistedColumns()),
                fn($value) => $value !== null,
            ));
        })->values()->all();
    }

    public static function bonusPercent(?string $runtimeLevel, ?string $method): float
    {
        $column = static::mainMethodColumn($method);

        if ($column === null) {
            return 0.0;
        }

        $setting = static::settingForRuntimeLevel($runtimeLevel);

        return round((float) ($setting[$column] ?? 0), 2);
    }

    public static function adminOpeningBalance(?string $runtimeLevel): float
    {
        $setting = static::settingForRuntimeLevel($runtimeLevel);

        return static::negativeAmount($setting['account_price'] ?? 0);
    }

    public static function selfOpeningBalance(?string $runtimeLevel): float
    {
        $setting = static::settingForRuntimeLevel($runtimeLevel);

        return static::negativeAmount($setting['self_account_price'] ?? 0);
    }

    protected static function persistedColumns(): array
    {
        return array_merge(['level', 'runtime_level', 'level_name', 'sort_order'], static::editableColumns());
    }

    protected static function settingForRuntimeLevel(?string $runtimeLevel): array
    {
        $default = collect(static::defaultRows())->firstWhere('runtime_level', (string) $runtimeLevel);

        if (! $default) {
            return [];
        }

        if (! Schema::hasTable((new static())->getTable())) {
            return $default;
        }

        $storedRow = static::query()->where('runtime_level', $runtimeLevel)->first();

        if (! $storedRow) {
            return $default;
        }

        return array_merge($default, array_filter(
            $storedRow->only(static::persistedColumns()),
            fn($value) => $value !== null,
        ));
    }

    protected static function mainMethodColumn(?string $method): ?string
    {
        return match (strtolower(trim((string) $method))) {
            'bkash' => 'bkash_main',
            'rocket' => 'rocket_main',
            'nagad' => 'nagad_main',
            'upay' => 'upay_main',
            default => null,
        };
    }

    protected static function negativeAmount($amount): float
    {
        $value = round((float) $amount, 2);

        return $value === 0.0 ? 0.0 : round(-abs($value), 2);
    }
}