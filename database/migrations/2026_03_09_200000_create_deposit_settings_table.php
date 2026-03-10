<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deposit_settings')) {
            return;
        }

        Schema::create('deposit_settings', function (Blueprint $table) {
            $table->id();
            $table->string('level')->unique();
            $table->string('runtime_level')->unique();
            $table->string('level_name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('bkash_main', 10, 2)->default(0);
            $table->decimal('rocket_main', 10, 2)->default(0);
            $table->decimal('nagad_main', 10, 2)->default(0);
            $table->decimal('upay_main', 10, 2)->default(0);
            $table->decimal('account_price', 10, 2)->default(0);
            $table->decimal('self_account_price', 10, 2)->default(0);
            $table->decimal('bkash_bank', 10, 2)->default(0);
            $table->decimal('rocket_bank', 10, 2)->default(0);
            $table->decimal('nagad_bank', 10, 2)->default(0);
            $table->decimal('upay_bank', 10, 2)->default(0);
            $table->decimal('bkash_drive', 10, 2)->default(0);
            $table->decimal('rocket_drive', 10, 2)->default(0);
            $table->decimal('nagad_drive', 10, 2)->default(0);
            $table->decimal('upay_drive', 10, 2)->default(0);
            $table->timestamps();
        });

        $timestamp = now();

        DB::table('deposit_settings')->insert([
            ['level' => 'subadmin', 'runtime_level' => 'subadmin', 'level_name' => 'subadmin', 'sort_order' => 1, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['level' => 'reseller5', 'runtime_level' => 'house', 'level_name' => 'HOUSE', 'sort_order' => 2, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['level' => 'reseller4', 'runtime_level' => 'dgm', 'level_name' => 'DGM', 'sort_order' => 3, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['level' => 'reseller3', 'runtime_level' => 'dealer', 'level_name' => 'Dealer', 'sort_order' => 4, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['level' => 'reseller2', 'runtime_level' => 'seller', 'level_name' => 'Seller', 'sort_order' => 5, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['level' => 'reseller1', 'runtime_level' => 'retailer', 'level_name' => 'Retailer', 'sort_order' => 6, 'bkash_main' => 2, 'rocket_main' => 2, 'nagad_main' => 2, 'upay_main' => 2, 'account_price' => 0, 'self_account_price' => 0, 'bkash_bank' => -1, 'rocket_bank' => -1, 'nagad_bank' => -1, 'upay_bank' => 0, 'bkash_drive' => 0, 'rocket_drive' => 0, 'nagad_drive' => 0, 'upay_drive' => 0, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_settings');
    }
};