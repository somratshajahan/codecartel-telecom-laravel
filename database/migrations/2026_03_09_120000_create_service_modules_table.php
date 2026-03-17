<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_modules')) {
            return;
        }

        Schema::create('service_modules', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->decimal('minimum_amount', 15, 2)->default(0);
            $table->decimal('maximum_amount', 15, 2)->default(0);
            $table->unsignedInteger('minimum_length')->default(0);
            $table->unsignedInteger('maximum_length')->default(0);
            $table->decimal('auto_send_limit', 15, 2)->default(0);
            $table->boolean('require_pin')->default(false);
            $table->boolean('require_name')->default(false);
            $table->boolean('require_nid')->default(false);
            $table->boolean('require_sender')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        $timestamp = now();

        DB::table('service_modules')->insert([
            ['title' => 'Flexiload', 'minimum_amount' => 10, 'maximum_amount' => 1499, 'minimum_length' => 11, 'maximum_length' => 11, 'auto_send_limit' => 1000.00, 'require_pin' => true, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 1, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'InternetPack', 'minimum_amount' => 5, 'maximum_amount' => 5000, 'minimum_length' => 11, 'maximum_length' => 11, 'auto_send_limit' => 296.00, 'require_pin' => true, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 2, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'Balance Transfer', 'minimum_amount' => 1, 'maximum_amount' => 1000000, 'minimum_length' => 3, 'maximum_length' => 30, 'auto_send_limit' => 100000.00, 'require_pin' => true, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 3, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'SMS', 'minimum_amount' => 1, 'maximum_amount' => 2000, 'minimum_length' => 11, 'maximum_length' => 11, 'auto_send_limit' => 500.00, 'require_pin' => true, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 4, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'Internet Banking', 'minimum_amount' => 500, 'maximum_amount' => 1000000, 'minimum_length' => 11, 'maximum_length' => 20, 'auto_send_limit' => 20000.00, 'require_pin' => false, 'require_name' => false, 'require_nid' => false, 'require_sender' => true, 'sort_order' => 5, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'Billpay', 'minimum_amount' => 50, 'maximum_amount' => 1000, 'minimum_length' => 5, 'maximum_length' => 15, 'auto_send_limit' => 300.00, 'require_pin' => true, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 5, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'Sonali Bank Limited', 'minimum_amount' => 10000, 'maximum_amount' => 1000000, 'minimum_length' => 3, 'maximum_length' => 20, 'auto_send_limit' => 25000.00, 'require_pin' => true, 'require_name' => true, 'require_nid' => true, 'require_sender' => true, 'sort_order' => 5, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'Bulk Flexi', 'minimum_amount' => 10, 'maximum_amount' => 5000, 'minimum_length' => 11, 'maximum_length' => 11, 'auto_send_limit' => 500.00, 'require_pin' => true, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 8, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'GlobalFlexi', 'minimum_amount' => 10, 'maximum_amount' => 5000, 'minimum_length' => 5, 'maximum_length' => 13, 'auto_send_limit' => 500.00, 'require_pin' => false, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 8, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'PrepaidCard', 'minimum_amount' => 9, 'maximum_amount' => 5000, 'minimum_length' => 5, 'maximum_length' => 30, 'auto_send_limit' => 1000.00, 'require_pin' => false, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 10, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'BillPay2', 'minimum_amount' => 10, 'maximum_amount' => 100000, 'minimum_length' => 3, 'maximum_length' => 90, 'auto_send_limit' => 5000.00, 'require_pin' => true, 'require_name' => false, 'require_nid' => false, 'require_sender' => false, 'sort_order' => 10, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['title' => 'BPO', 'minimum_amount' => 1000, 'maximum_amount' => 50000, 'minimum_length' => 11, 'maximum_length' => 11, 'auto_send_limit' => 10200.00, 'require_pin' => true, 'require_name' => false, 'require_nid' => false, 'require_sender' => true, 'sort_order' => 12, 'status' => 'active', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_modules');
    }
};