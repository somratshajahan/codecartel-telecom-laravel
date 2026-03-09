<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DeviceApprovalService;
use App\Services\FirebasePushNotificationService;
use App\Services\GoogleOtpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'array']);

        Schema::dropIfExists('site_notices');
        Schema::dropIfExists('operators');
        Schema::dropIfExists('homepage_settings');
        Schema::dropIfExists('brandings');
        Schema::dropIfExists('device_logs');
        Schema::dropIfExists('flexi_requests');
        Schema::dropIfExists('manual_payment_requests');
        Schema::dropIfExists('api_routes');
        Schema::dropIfExists('api_connection_approvals');
        Schema::dropIfExists('apis');
        Schema::dropIfExists('api_domains');
        Schema::dropIfExists('service_modules');
        Schema::dropIfExists('otps');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->nullable();
            $table->string('password');
            $table->string('pin')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_first_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->longText('permissions')->nullable();
            $table->string('level')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->decimal('main_bal', 15, 2)->default(0);
            $table->decimal('drive_bal', 15, 2)->default(0);
            $table->decimal('bank_bal', 15, 2)->default(0);
            $table->longText('fcm_token')->nullable();
            $table->timestamp('fcm_token_updated_at')->nullable();
            $table->text('google_otp_secret')->nullable();
            $table->boolean('google_otp_enabled')->default(false);
            $table->timestamp('google_otp_confirmed_at')->nullable();
            $table->string('api_key')->nullable();
            $table->boolean('api_access_enabled')->default(false);
            $table->longText('api_services')->nullable();
            $table->rememberToken()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('api_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('domain');
            $table->string('provider')->default('Etross');
            $table->timestamps();
        });

        Schema::create('apis', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('user_id');
            $table->string('api_key')->nullable();
            $table->string('provider');
            $table->string('api_url')->nullable();
            $table->string('status')->default('active');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('main_balance', 15, 2)->nullable();
            $table->decimal('drive_balance', 15, 2)->nullable();
            $table->decimal('bank_balance', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('api_connection_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_id')->unique();
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('api_routes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('module_type')->default('manual');
            $table->string('module_name');
            $table->unsignedBigInteger('api_id')->nullable();
            $table->string('service')->default('all');
            $table->string('code')->default('all');
            $table->unsignedInteger('priority')->default(1);
            $table->string('prefix')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('homepage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('company_logo_url')->nullable();
            $table->string('footer_company_name')->nullable();
            $table->text('footer_description')->nullable();
            $table->string('firebase_api_key')->nullable();
            $table->string('firebase_auth_domain')->nullable();
            $table->string('firebase_project_id')->nullable();
            $table->string('firebase_storage_bucket')->nullable();
            $table->string('firebase_messaging_sender_id')->nullable();
            $table->string('firebase_app_id')->nullable();
            $table->text('firebase_vapid_key')->nullable();
            $table->longText('firebase_service_account_json')->nullable();
            $table->boolean('google_otp_enabled')->default(false);
            $table->string('google_otp_issuer')->nullable();
            $table->timestamps();
        });

        Schema::create('brandings', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name')->nullable();
            $table->string('drive_balance')->nullable();
            $table->string('bkash')->nullable();
            $table->string('rocket')->nullable();
            $table->string('nagad')->nullable();
            $table->string('upay')->nullable();
            $table->string('sslcommerz_store_id')->nullable();
            $table->string('sslcommerz_store_password')->nullable();
            $table->string('sslcommerz_mode')->nullable();
            $table->string('amarpay_store_id')->nullable();
            $table->string('amarpay_signature_key')->nullable();
            $table->string('amarpay_mode')->nullable();
            $table->timestamps();
        });

        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('short_code')->nullable();
            $table->string('description')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('circle_bg_color')->default('#0078C8');
            $table->string('logo_text')->nullable();
            $table->string('logo_image_url')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('site_notices', function (Blueprint $table) {
            $table->id();
            $table->text('notice_text')->nullable();
        });

        Schema::create('device_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('username');
            $table->string('browser_os');
            $table->boolean('two_step_verified')->default(false);
            $table->enum('status', ['active', 'deactive'])->default('deactive');
            $table->timestamps();
        });
    }

    public function test_homepage_loads_and_shows_database_notice(): void
    {
        DB::table('site_notices')->insert([
            'notice_text' => 'Admin notice from database',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Admin notice from database');
    }

    public function test_homepage_shows_fallback_operators_when_database_is_empty(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Supported Telecom Operators');
        $response->assertSee('Grameenphone');
        $response->assertSee('Banglalink');
    }

    public function test_homepage_shows_company_logo_in_footer_and_navbar(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'footer_company_name' => 'Codecartel',
            'company_logo_url' => 'uploads/company-logo.png',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('uploads/company-logo.png');
    }

    public function test_homepage_shows_provider_api_docs_section_and_nav_link(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Simple Provider API Documentation');
        $response->assertSee('POST /api/v1/auth-check');
        $response->assertSee('X-Client-Domain: yourdomain.com');
        $response->assertSee('href="#docs"', false);
    }

    public function test_homepage_shows_uploaded_operator_logo_when_available(): void
    {
        DB::table('operators')->insert([
            'name' => 'Grameenphone',
            'short_code' => 'GP',
            'logo_text' => 'GP',
            'description' => 'Prepaid & Postpaid',
            'circle_bg_color' => '#0078C8',
            'logo_image_url' => 'uploads/gp-logo.png',
            'logo' => 'uploads/gp-logo.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('uploads/gp-logo.png');
        $response->assertSee('Grameenphone');
    }

    public function test_internet_selection_page_shows_uploaded_operator_logo(): void
    {
        DB::table('operators')->insert([
            'name' => 'Grameenphone',
            'short_code' => 'GP',
            'logo_text' => 'GP',
            'circle_bg_color' => '#0078C8',
            'logo_image_url' => 'uploads/gp-logo.png',
            'logo' => 'uploads/gp-logo.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = new User();
        $user->forceFill(['id' => 1, 'name' => 'Demo User', 'permissions' => json_encode(['internet'])]);

        $response = $this->actingAs($user)->get('/internet-packs');

        $response->assertOk();
        $response->assertSee('Select Operator (Internet Pack)');
        $response->assertSee('uploads/gp-logo.png');
    }

    public function test_drive_selection_page_shows_uploaded_operator_logo(): void
    {
        DB::table('operators')->insert([
            'name' => 'Grameenphone',
            'short_code' => 'GP',
            'logo_text' => 'GP',
            'circle_bg_color' => '#0078C8',
            'logo_image_url' => 'uploads/gp-logo.png',
            'logo' => 'uploads/gp-logo.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = new User();
        $user->forceFill(['id' => 1, 'name' => 'Demo User', 'permissions' => json_encode(['drive'])]);

        $response = $this->actingAs($user)->get('/drive-offers');

        $response->assertOk();
        $response->assertSee('Select Operator');
        $response->assertSee('uploads/gp-logo.png');
    }

    public function test_flexi_page_shows_selected_operator_and_user_navigation(): void
    {
        DB::table('operators')->insert([
            'name' => 'Grameenphone',
            'short_code' => 'GP',
            'logo_text' => 'GP',
            'circle_bg_color' => '#0078C8',
            'logo_image_url' => 'uploads/gp-logo.png',
            'logo' => 'uploads/gp-logo.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = new User();
        $user->forceFill(['id' => 1, 'name' => 'Demo User']);

        $response = $this->actingAs($user)->get('/flexiload?operator=Grameenphone');

        $response->assertOk();
        $response->assertSee('Send Flexiload');
        $response->assertSee('Choice Operator');
        $response->assertSee('Grameenphone');
        $response->assertSee('value="GrameenPhone" selected', false);
        $response->assertSee('Dashboard');
        $response->assertSee('New Request');
        $response->assertSee('History');
        $response->assertSee('Logout');
        $response->assertSee('Demo User');
    }

    public function test_flexi_page_shows_send_panel_and_empty_last_requests_state(): void
    {
        $this->ensureFlexiRequestsTable();

        DB::table('operators')->insert([
            'name' => 'Grameenphone',
            'short_code' => 'GP',
            'logo_text' => 'GP',
            'circle_bg_color' => '#0078C8',
            'logo_image_url' => 'uploads/gp-logo.png',
            'logo' => 'uploads/gp-logo.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = $this->createLoginUser(106, [
            'name' => 'Flexi Panel User',
            'email' => 'flexi-panel-user@example.com',
        ]);

        $response = $this->actingAs($user)->get('/flexiload?operator=GrameenPhone');

        $response->assertOk();
        $response->assertSee('Send Flexiload');
        $response->assertSee('Choice Operator');
        $response->assertSee('eg: 0171XXXXXXX');
        $response->assertSee('[ Min Number 11, Max Number 11 ]');
        $response->assertSee('eg: 100');
        $response->assertSee('[ Min Amount 10, Max Amount 1499 ]');
        $response->assertSee('Prepaid');
        $response->assertSee('Postpaid');
        $response->assertSee('User PIN');
        $response->assertSee('name="pin"', false);
        $response->assertSee('Last 10 Requests');
        $response->assertSee('No Requests Found.');
        $response->assertSee('id="detectedOperatorText" class="label-text-alt text-primary hidden"', false);
    }

    public function test_flexi_request_submission_auto_detects_operator_and_shows_in_last_requests(): void
    {
        $this->ensureFlexiRequestsTable();

        DB::table('operators')->insert([
            [
                'name' => 'Grameenphone',
                'short_code' => 'GP',
                'logo_text' => 'GP',
                'circle_bg_color' => '#0078C8',
                'logo_image_url' => 'uploads/gp-logo.png',
                'logo' => 'uploads/gp-logo.png',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Robi',
                'short_code' => 'RB',
                'logo_text' => 'RB',
                'circle_bg_color' => '#E60000',
                'logo_image_url' => 'uploads/robi-logo.png',
                'logo' => 'uploads/robi-logo.png',
                'is_active' => true,
                'sort_order' => 2,
            ],
        ]);

        $user = $this->createLoginUser(107, [
            'name' => 'Flexi Auto Detect User',
            'email' => 'flexi-auto-detect@example.com',
            'main_bal' => 500,
        ]);

        $this->actingAs($user)
            ->post('/flexiload', [
                'operator' => '',
                'number' => '01712345678',
                'amount' => '100',
                'type' => 'Postpaid',
                'pin' => '1234',
            ])
            ->assertRedirect(route('user.flexi', ['operator' => 'GrameenPhone']));

        $this->assertDatabaseHas('flexi_requests', [
            'user_id' => $user->id,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 100,
            'cost' => 100,
            'type' => 'Postpaid',
            'trnx_id' => null,
            'status' => 'pending',
        ]);

        $this->assertSame(400.0, (float) $user->fresh()->main_bal);

        $this->actingAs($user)
            ->get('/flexiload')
            ->assertOk()
            ->assertSee('01712345678')
            ->assertSee('100.00')
            ->assertSee('-')
            ->assertSee('pending');
    }

    public function test_flexi_request_validates_number_amount_operator_and_pin(): void
    {
        $this->ensureFlexiRequestsTable();

        $user = $this->createLoginUser(108, [
            'name' => 'Flexi Validation User',
            'email' => 'flexi-validation@example.com',
            'main_bal' => 500,
        ]);

        $this->actingAs($user)
            ->from('/flexiload')
            ->post('/flexiload', [
                'operator' => '',
                'number' => '0171234',
                'amount' => '9',
                'type' => 'Prepaid',
                'pin' => '',
            ])
            ->assertRedirect('/flexiload')
            ->assertSessionHasErrors(['number', 'amount', 'pin']);

        $this->actingAs($user)
            ->from('/flexiload')
            ->post('/flexiload', [
                'operator' => '',
                'number' => '01112345678',
                'amount' => '100',
                'type' => 'Prepaid',
                'pin' => '1234',
            ])
            ->assertRedirect('/flexiload')
            ->assertSessionHasErrors(['operator']);

        $this->assertDatabaseCount('flexi_requests', 0);
    }

    public function test_flexi_request_requires_valid_user_pin_and_available_main_balance(): void
    {
        $this->ensureFlexiRequestsTable();

        DB::table('operators')->insert([
            'name' => 'Grameenphone',
            'short_code' => 'GP',
            'logo_text' => 'GP',
            'circle_bg_color' => '#0078C8',
            'logo_image_url' => 'uploads/gp-logo.png',
            'logo' => 'uploads/gp-logo.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = $this->createLoginUser(109, [
            'name' => 'Flexi Pin Balance User',
            'email' => 'flexi-pin-balance@example.com',
            'main_bal' => 80,
        ]);

        $this->actingAs($user)
            ->from('/flexiload')
            ->post('/flexiload', [
                'operator' => 'GrameenPhone',
                'number' => '01712345678',
                'amount' => '50',
                'type' => 'Prepaid',
                'pin' => '9999',
            ])
            ->assertRedirect('/flexiload?operator=GrameenPhone')
            ->assertSessionHasErrors(['pin']);

        $this->actingAs($user)
            ->from('/flexiload')
            ->post('/flexiload', [
                'operator' => 'GrameenPhone',
                'number' => '01712345678',
                'amount' => '100',
                'type' => 'Prepaid',
                'pin' => '1234',
            ])
            ->assertRedirect('/flexiload?operator=GrameenPhone')
            ->assertSessionHasErrors(['amount']);

        $this->assertDatabaseCount('flexi_requests', 0);
        $this->assertSame(80.0, (float) $user->fresh()->main_bal);
    }

    public function test_flexi_pending_request_shows_in_user_and_admin_pending_lists(): void
    {
        $this->ensureFlexiRequestsTable();

        if (! Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        $admin = $this->createLoginUser(110, [
            'name' => 'Flexi Pending Admin',
            'email' => 'flexi-pending-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(111, [
            'name' => 'Flexi Pending User',
            'email' => 'flexi-pending-user@example.com',
        ]);

        DB::table('flexi_requests')->insert([
            'id' => 1,
            'user_id' => $user->id,
            'operator' => 'Robi',
            'mobile' => '01812345678',
            'amount' => 200,
            'cost' => 200,
            'type' => 'Prepaid',
            'trnx_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/my-pending-requests')
            ->assertOk()
            ->assertSee('My Pending Requests')
            ->assertSee('SL')
            ->assertSee('Flexi')
            ->assertSee('Prepaid')
            ->assertSee('Robi')
            ->assertSee('01812345678');

        $adminResponse = $this->actingAs($admin)->get('/admin/pending-drive-requests?service=flexi');

        $adminResponse->assertOk()
            ->assertSee('Flexi')
            ->assertSee('Flexi Pending User')
            ->assertSee('01812345678')
            ->assertSee('Prepaid')
            ->assertSee('name="bulk_action"', false)
            ->assertSee('id="pending-bulk-select-all"', false)
            ->assertDontSee('value="flexi:1"', false)
            ->assertSee('/admin/flexi-requests/1/approve', false)
            ->assertSee('/admin/flexi-requests/1/failed', false)
            ->assertSee('/admin/flexi-requests/1/cancel', false);
    }

    public function test_admin_can_approve_flexi_request_and_set_transaction_id(): void
    {
        $this->ensureFlexiRequestsTable();

        $admin = $this->createLoginUser(112, [
            'name' => 'Flexi Action Admin',
            'email' => 'flexi-action-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(113, [
            'name' => 'Flexi Approve User',
            'email' => 'flexi-approve-user@example.com',
            'main_bal' => 300,
        ]);

        DB::table('flexi_requests')->insert([
            'id' => 1,
            'user_id' => $user->id,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 100,
            'cost' => 100,
            'type' => 'Postpaid',
            'trnx_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/1/approve')
            ->assertOk()
            ->assertSee('Confirm Flexi Request')
            ->assertSee('Postpaid')
            ->assertSee('01712345678');

        $this->actingAs($admin)
            ->from('/admin/flexi-requests/1/approve')
            ->post('/admin/flexi-requests/1/confirm', [
                'trnx_id' => 'FLX-ADMIN-001',
                'pin' => '9999',
            ])
            ->assertRedirect('/admin/flexi-requests/1/approve')
            ->assertSessionHas('error', 'Invalid PIN!');

        $this->assertDatabaseHas('flexi_requests', [
            'id' => 1,
            'status' => 'pending',
            'trnx_id' => null,
        ]);

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/1/confirm', [
                'trnx_id' => 'FLX-ADMIN-001',
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Flexi request approved successfully!');

        $this->assertDatabaseHas('flexi_requests', [
            'id' => 1,
            'status' => 'approved',
            'trnx_id' => 'FLX-ADMIN-001',
        ]);

        $this->assertSame(300.0, (float) $user->fresh()->main_bal);

        $this->actingAs($user)
            ->get('/flexiload')
            ->assertOk()
            ->assertSee('FLX-ADMIN-001')
            ->assertSee('approved');
    }

    public function test_admin_can_fail_and_cancel_flexi_requests_and_refund_main_balance(): void
    {
        $this->ensureFlexiRequestsTable();

        $admin = $this->createLoginUser(114, [
            'name' => 'Flexi Refund Admin',
            'email' => 'flexi-refund-admin@example.com',
            'is_admin' => true,
        ]);

        $failedUser = $this->createLoginUser(115, [
            'name' => 'Flexi Failed User',
            'email' => 'flexi-failed-user@example.com',
            'main_bal' => 250,
        ]);

        $cancelUser = $this->createLoginUser(116, [
            'name' => 'Flexi Cancel User',
            'email' => 'flexi-cancel-user@example.com',
            'main_bal' => 180,
        ]);

        DB::table('flexi_requests')->insert([
            [
                'id' => 1,
                'user_id' => $failedUser->id,
                'operator' => 'Robi',
                'mobile' => '01812345678',
                'amount' => 70,
                'cost' => 70,
                'type' => 'Prepaid',
                'trnx_id' => null,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => $cancelUser->id,
                'operator' => 'Banglalink',
                'mobile' => '01912345678',
                'amount' => 90,
                'cost' => 90,
                'type' => 'Postpaid',
                'trnx_id' => null,
                'status' => 'pending',
                'created_at' => now()->addSecond(),
                'updated_at' => now()->addSecond(),
            ],
        ]);

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/1/failed')
            ->assertOk()
            ->assertSee('Confirm Failed Flexi Request')
            ->assertSee('Robi');

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/1/confirm-failed', [
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Flexi request failed and balance refunded!');

        $this->assertDatabaseHas('flexi_requests', [
            'id' => 1,
            'status' => 'rejected',
        ]);

        $this->assertSame(320.0, (float) $failedUser->fresh()->main_bal);

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/2/cancel')
            ->assertOk()
            ->assertSee('Confirm Cancel Flexi Request')
            ->assertSee('Banglalink');

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/2/confirm-cancel', [
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Flexi request cancelled and balance refunded!');

        $this->assertDatabaseHas('flexi_requests', [
            'id' => 2,
            'status' => 'rejected',
        ]);

        $this->assertSame(270.0, (float) $cancelUser->fresh()->main_bal);

        $this->actingAs($failedUser)
            ->get('/flexiload')
            ->assertOk()
            ->assertSee('rejected')
            ->assertSee('01812345678');

        $this->actingAs($cancelUser)
            ->get('/flexiload')
            ->assertOk()
            ->assertSee('rejected')
            ->assertSee('01912345678');
    }

    public function test_admin_can_sync_routed_flexi_request_approval_to_source_system(): void
    {
        $this->ensureFlexiRequestsTable();

        $admin = $this->createLoginUser(117, [
            'name' => 'Routed Flexi Approve Admin',
            'email' => 'routed-flexi-approve-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(118, [
            'name' => 'Routed Flexi Approve User',
            'email' => 'routed-flexi-approve-user@example.com',
            'main_bal' => 300,
        ]);

        Http::fake([
            'https://source.example.test/api/v1/routed-settlement' => Http::response(['status' => 'success'], 200),
        ]);

        DB::table('flexi_requests')->insert([
            'id' => 101,
            'user_id' => $user->id,
            'operator' => 'Grameenphone',
            'mobile' => '01755555555',
            'amount' => 100,
            'cost' => 100,
            'type' => 'Postpaid',
            'trnx_id' => null,
            'status' => 'pending',
            'is_routed' => true,
            'route_api_id' => 1,
            'remote_request_id' => 'provider-flexi-101',
            'source_request_id' => '501',
            'source_request_type' => 'recharge',
            'source_api_key' => 'source-key-approve',
            'source_callback_url' => 'https://source.example.test/api/v1/routed-settlement',
            'source_client_domain' => 'source.example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/101/confirm', [
                'trnx_id' => 'FLX-ROUTED-101',
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Flexi request approved successfully!');

        $this->assertDatabaseHas('flexi_requests', [
            'id' => 101,
            'status' => 'approved',
            'trnx_id' => 'FLX-ROUTED-101',
        ]);

        $this->assertSame(300.0, (float) $user->fresh()->main_bal);

        Http::assertSentCount(1);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'https://source.example.test/api/v1/routed-settlement'
                && (($request->header('X-API-KEY')[0] ?? null) === 'source-key-approve')
                && (($request->header('X-Client-Domain')[0] ?? null) === 'source.example.test')
                && (($request->data()['source_request_id'] ?? null) === 501)
                && (($request->data()['request_type'] ?? null) === 'recharge')
                && (($request->data()['status'] ?? null) === 'approved')
                && (($request->data()['remote_request_id'] ?? null) === 'provider-flexi-101')
                && (($request->data()['trnx_id'] ?? null) === 'FLX-ROUTED-101')
                && (($request->data()['domain'] ?? null) === 'source.example.test');
        });
    }

    public function test_admin_can_sync_routed_flexi_fail_and_cancel_without_local_refund(): void
    {
        $this->ensureFlexiRequestsTable();

        $admin = $this->createLoginUser(119, [
            'name' => 'Routed Flexi Fail Admin',
            'email' => 'routed-flexi-fail-admin@example.com',
            'is_admin' => true,
        ]);

        $failedUser = $this->createLoginUser(120, [
            'name' => 'Routed Flexi Failed User',
            'email' => 'routed-flexi-failed-user@example.com',
            'main_bal' => 250,
        ]);

        $cancelUser = $this->createLoginUser(121, [
            'name' => 'Routed Flexi Cancel User',
            'email' => 'routed-flexi-cancel-user@example.com',
            'main_bal' => 180,
        ]);

        Http::fake([
            'https://source.example.test/api/v1/routed-settlement' => Http::response(['status' => 'success'], 200),
        ]);

        DB::table('flexi_requests')->insert([
            [
                'id' => 102,
                'user_id' => $failedUser->id,
                'operator' => 'Robi',
                'mobile' => '01855555555',
                'amount' => 70,
                'cost' => 70,
                'type' => 'Prepaid',
                'trnx_id' => null,
                'status' => 'pending',
                'is_routed' => true,
                'route_api_id' => 1,
                'remote_request_id' => 'provider-flexi-102',
                'source_request_id' => '502',
                'source_request_type' => 'recharge',
                'source_api_key' => 'source-key-failed',
                'source_callback_url' => 'https://source.example.test/api/v1/routed-settlement',
                'source_client_domain' => 'source.example.test',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 103,
                'user_id' => $cancelUser->id,
                'operator' => 'Banglalink',
                'mobile' => '01955555555',
                'amount' => 90,
                'cost' => 90,
                'type' => 'Postpaid',
                'trnx_id' => null,
                'status' => 'pending',
                'is_routed' => true,
                'route_api_id' => 1,
                'remote_request_id' => 'provider-flexi-103',
                'source_request_id' => '503',
                'source_request_type' => 'recharge',
                'source_api_key' => 'source-key-cancel',
                'source_callback_url' => 'https://source.example.test/api/v1/routed-settlement',
                'source_client_domain' => 'source.example.test',
                'created_at' => now()->addSecond(),
                'updated_at' => now()->addSecond(),
            ],
        ]);

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/102/confirm-failed', [
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Flexi request failed successfully!');

        $this->actingAs($admin)
            ->post('/admin/flexi-requests/103/confirm-cancel', [
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Flexi request cancelled successfully!');

        $this->assertDatabaseHas('flexi_requests', [
            'id' => 102,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('flexi_requests', [
            'id' => 103,
            'status' => 'rejected',
        ]);

        $this->assertSame(250.0, (float) $failedUser->fresh()->main_bal);
        $this->assertSame(180.0, (float) $cancelUser->fresh()->main_bal);

        Http::assertSentCount(2);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return (($request->data()['source_request_id'] ?? null) === 502)
                && (($request->data()['status'] ?? null) === 'rejected')
                && (($request->data()['remote_request_id'] ?? null) === 'provider-flexi-102');
        });
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return (($request->data()['source_request_id'] ?? null) === 503)
                && (($request->data()['status'] ?? null) === 'cancelled')
                && (($request->data()['remote_request_id'] ?? null) === 'provider-flexi-103');
        });
    }

    public function test_routed_callback_failure_blocks_provider_finalization(): void
    {
        $this->ensureFlexiRequestsTable();

        $admin = $this->createLoginUser(122, [
            'name' => 'Routed Callback Failure Admin',
            'email' => 'routed-callback-failure-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(123, [
            'name' => 'Routed Callback Failure User',
            'email' => 'routed-callback-failure-user@example.com',
            'main_bal' => 210,
        ]);

        Http::fake([
            'https://source.example.test/api/v1/routed-settlement' => Http::response([
                'status' => 'error',
                'message' => 'Source rejected settlement.',
            ], 500),
        ]);

        DB::table('flexi_requests')->insert([
            'id' => 104,
            'user_id' => $user->id,
            'operator' => 'Teletalk',
            'mobile' => '01555555555',
            'amount' => 60,
            'cost' => 60,
            'type' => 'Prepaid',
            'trnx_id' => null,
            'status' => 'pending',
            'is_routed' => true,
            'route_api_id' => 1,
            'remote_request_id' => 'provider-flexi-104',
            'source_request_id' => '504',
            'source_request_type' => 'recharge',
            'source_api_key' => 'source-key-error',
            'source_callback_url' => 'https://source.example.test/api/v1/routed-settlement',
            'source_client_domain' => 'source.example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/admin/flexi-requests/104/approve')
            ->post('/admin/flexi-requests/104/confirm', [
                'trnx_id' => 'FLX-ROUTED-FAIL',
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/flexi-requests/104/approve')
            ->assertSessionHas('error', 'Unable to sync routed request with source system.');

        $this->assertDatabaseHas('flexi_requests', [
            'id' => 104,
            'status' => 'pending',
            'trnx_id' => null,
        ]);

        Http::assertSentCount(1);
    }

    public function test_admin_drive_approval_requires_four_digit_pin_and_keeps_balance_unchanged_on_success(): void
    {
        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('balance_type')->nullable();
            $table->timestamps();
        });

        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $admin = $this->createLoginUser(117, [
            'name' => 'Drive Approve Admin',
            'email' => 'drive-approve-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(118, [
            'name' => 'Drive Approve User',
            'email' => 'drive-approve-user@example.com',
            'main_bal' => 400,
            'drive_bal' => 110,
        ]);

        DB::table('drive_requests')->insert([
            'id' => 1,
            'user_id' => $user->id,
            'package_id' => null,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 90,
            'status' => 'pending',
            'balance_type' => 'drive_bal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/admin/drive-requests/1/approve')
            ->post('/admin/drive-requests/1/confirm', [
                'description' => 'Manual success',
                'pin' => '123',
            ])
            ->assertRedirect('/admin/drive-requests/1/approve')
            ->assertSessionHasErrors(['pin']);

        $this->assertDatabaseHas('drive_requests', [
            'id' => 1,
            'status' => 'pending',
        ]);
        $this->assertSame(110.0, (float) $user->fresh()->drive_bal);

        $this->actingAs($admin)
            ->post('/admin/drive-requests/1/confirm', [
                'description' => 'Manual success',
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Request approved successfully!');

        $this->assertDatabaseHas('drive_requests', [
            'id' => 1,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('drive_history', [
            'user_id' => $user->id,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 90,
            'status' => 'success',
            'description' => 'Manual success',
        ]);
        $this->assertSame(110.0, (float) $user->fresh()->drive_bal);
        $this->assertSame(400.0, (float) $user->fresh()->main_bal);
    }

    public function test_admin_regular_approval_requires_four_digit_pin_and_keeps_balance_unchanged_on_success(): void
    {
        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $admin = $this->createLoginUser(119, [
            'name' => 'Regular Approve Admin',
            'email' => 'regular-approve-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(120, [
            'name' => 'Regular Approve User',
            'email' => 'regular-approve-user@example.com',
            'main_bal' => 120,
        ]);

        DB::table('regular_requests')->insert([
            'id' => 1,
            'user_id' => $user->id,
            'package_id' => null,
            'operator' => 'Banglalink',
            'mobile' => '01912345678',
            'amount' => 80,
            'status' => 'pending',
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/admin/regular-requests/1/approve')
            ->post('/admin/regular-requests/1/confirm', [
                'description' => 'Completed by admin',
                'pin' => '12',
            ])
            ->assertRedirect('/admin/regular-requests/1/approve')
            ->assertSessionHasErrors(['pin']);

        $this->assertDatabaseHas('regular_requests', [
            'id' => 1,
            'status' => 'pending',
        ]);
        $this->assertSame(120.0, (float) $user->fresh()->main_bal);

        $this->actingAs($admin)
            ->post('/admin/regular-requests/1/confirm', [
                'description' => 'Completed by admin',
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Regular request approved successfully!');

        $this->assertDatabaseHas('regular_requests', [
            'id' => 1,
            'status' => 'approved',
            'description' => 'Completed by admin',
        ]);
        $this->assertSame(120.0, (float) $user->fresh()->main_bal);
    }

    public function test_admin_login_page_loads_successfully(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->get('/admin/login')
            ->assertOk()
            ->assertSee('Device IP: 203.0.113.50');
    }

    public function test_user_login_page_shows_current_device_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.51'])
            ->get('/login')
            ->assertOk()
            ->assertSee('Device IP: 203.0.113.51');
    }

    public function test_user_login_page_mentions_google_otp_next_step_when_enabled_globally(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'google_otp_enabled' => true,
            'google_otp_issuer' => 'Codecartel Secure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('If Google OTP is enabled on your account')
            ->assertSee('next page');
    }

    public function test_admin_notice_page_loads_successfully(): void
    {
        $this->get('/admin/notice')
            ->assertOk()
            ->assertSee('Login Notice')
            ->assertSee('Dashboard');
    }

    public function test_admin_homepage_hides_removed_sections_and_shows_operator_logos(): void
    {
        $admin = new User();
        $admin->forceFill([
            'id' => 99,
            'name' => 'Admin User',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/admin/homepage')
            ->assertOk()
            ->assertSee('Operator Logos')
            ->assertDontSee('Operators Section')
            ->assertDontSee('Features Section')
            ->assertDontSee('Statistics');
    }

    public function test_guest_is_redirected_to_admin_login_from_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_renders_chart_canvases_and_payload(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('recharge_history')) {
            Schema::create('recharge_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->decimal('amount', 15, 2);
                $table->string('type')->nullable();
                $table->timestamps();
            });
        }

        $admin = $this->createLoginUser(410, [
            'name' => 'Dashboard Admin',
            'email' => 'dashboard-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(411, [
            'name' => 'Dashboard User',
            'email' => 'dashboard-user@example.com',
        ]);

        DB::table('recharge_history')->insert([
            [
                'user_id' => $user->id,
                'amount' => 150,
                'type' => 'Grameenphone',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'amount' => 75,
                'type' => 'Bkash',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('balance_add_history')->insert([
            'user_id' => $user->id,
            'amount' => 250,
            'type' => 'bkash',
            'description' => 'Dashboard balance add',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('id="rechargeChart"', false)
            ->assertSee('id="balanceChart"', false)
            ->assertSee('id="operatorChart"', false)
            ->assertSee('id="bankingChart"', false)
            ->assertSee('chart.umd.min.js', false)
            ->assertSee('type="application/json"', false)
            ->assertSee('chart-payload-data', false)
            ->assertSee('Grameenphone', false)
            ->assertSee('Bkash', false);
    }

    public function test_user_first_device_login_is_allowed_and_saved_in_device_logs(): void
    {
        $user = $this->createLoginUser(401, [
            'name' => 'Device User',
            'email' => 'device-user@example.com',
        ]);

        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post('/login', [
                'email' => 'device-user@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ]);

        $response->assertRedirect('/dashboard');
        $response->assertCookie(DeviceApprovalService::COOKIE_NAME);
        $this->assertAuthenticatedAs($user);

        $log = DB::table('device_logs')->where('username', 'device-user@example.com')->first();

        $this->assertNotNull($log);
        $this->assertSame('203.0.113.10', $log->ip_address);
        $this->assertSame('active', $log->status);
        $this->assertStringContainsString('Chrome', $log->browser_os);
        $this->assertStringContainsString('Windows', $log->browser_os);
        $this->assertStringContainsString('Key:', $log->browser_os);
    }

    public function test_new_device_login_is_blocked_until_admin_approval(): void
    {
        $this->createLoginUser(402, [
            'name' => 'Blocked Device User',
            'email' => 'blocked-device@example.com',
        ]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->post('/login', [
                'email' => 'blocked-device@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ])
            ->assertRedirect('/dashboard');

        $this->post('/logout')->assertRedirect('/');

        $response = $this->from('/login')
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Mobile/15E148 Safari/604.1')
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.22'])
            ->post('/login', [
                'email' => 'blocked-device@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'New device detected. Please wait for admin approval before login.',
        ]);
        $response->assertCookie(DeviceApprovalService::COOKIE_NAME);
        $this->assertGuest();

        $this->assertSame(2, DB::table('device_logs')->where('username', 'blocked-device@example.com')->count());
        $this->assertSame('deactive', DB::table('device_logs')->where('username', 'blocked-device@example.com')->latest('id')->value('status'));
        $this->assertSame('198.51.100.22', DB::table('device_logs')->where('username', 'blocked-device@example.com')->latest('id')->value('ip_address'));
    }

    public function test_same_device_with_changed_ip_is_blocked_until_admin_approval(): void
    {
        $this->createLoginUser(412, [
            'name' => 'IP Changed User',
            'email' => 'ip-changed@example.com',
        ]);

        $trustedToken = 'same-device-token-412';

        $this->withCookie(DeviceApprovalService::COOKIE_NAME, $trustedToken)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.41'])
            ->post('/login', [
                'email' => 'ip-changed@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ])
            ->assertRedirect('/dashboard');

        $this->post('/logout')->assertRedirect('/');

        $response = $this->from('/login')
            ->withCookie(DeviceApprovalService::COOKIE_NAME, $trustedToken)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36')
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.41'])
            ->post('/login', [
                'email' => 'ip-changed@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'New device detected. Please wait for admin approval before login.',
        ]);
        $this->assertGuest();
        $this->assertSame('deactive', DB::table('device_logs')->where('username', 'ip-changed@example.com')->latest('id')->value('status'));
        $this->assertSame('198.51.100.41', DB::table('device_logs')->where('username', 'ip-changed@example.com')->latest('id')->value('ip_address'));
    }

    public function test_admin_can_approve_pending_device_and_user_can_login_afterwards(): void
    {
        $user = $this->createLoginUser(403, [
            'name' => 'Approved Device User',
            'email' => 'approved-device@example.com',
        ]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.12'])
            ->post('/login', [
                'email' => 'approved-device@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ])
            ->assertRedirect('/dashboard');

        $this->post('/logout')->assertRedirect('/');

        $pendingDeviceToken = 'pending-device-token-403';

        $this->from('/login')
            ->withCookie(DeviceApprovalService::COOKIE_NAME, $pendingDeviceToken)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Mobile Safari/537.36')
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.23'])
            ->post('/login', [
                'email' => 'approved-device@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'New device detected. Please wait for admin approval before login.',
            ]);

        $pendingLogId = DB::table('device_logs')
            ->where('username', 'approved-device@example.com')
            ->where('status', 'deactive')
            ->value('id');

        $admin = new User();
        $admin->forceFill([
            'id' => 904,
            'name' => 'Device Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->from('/admin/device-logs')
            ->post('/admin/device-logs/' . $pendingLogId . '/approve')
            ->assertRedirect('/admin/device-logs');

        $this->assertSame('active', DB::table('device_logs')->where('id', $pendingLogId)->value('status'));

        $this->post('/logout')->assertRedirect('/admin/login');

        $response = $this->withCookie(DeviceApprovalService::COOKIE_NAME, $pendingDeviceToken)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Mobile Safari/537.36')
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.23'])
            ->post('/login', [
                'email' => 'approved-device@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_admin_device_logs_page_shows_refined_ui_sections(): void
    {
        $admin = $this->createLoginUser(907, [
            'name' => 'Device Logs Admin',
            'email' => 'device-logs-admin@example.com',
            'is_admin' => true,
        ]);

        $this->createLoginUser(908, [
            'name' => 'Device Logs User',
            'email' => 'device-logs-user@example.com',
        ]);

        DB::table('device_logs')->insert([
            'ip_address' => '198.51.100.80',
            'username' => 'device-logs-user@example.com',
            'browser_os' => 'Desktop | Chrome 145.0.0.0 | Windows 11',
            'two_step_verified' => false,
            'status' => 'deactive',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/device-logs')
            ->assertOk()
            ->assertSee('Device Logs Overview')
            ->assertSee('Device access activity')
            ->assertSee('Total Logs')
            ->assertSee('Pending Review')
            ->assertSee('Apply Filter')
            ->assertSee('Approve Device');
    }

    public function test_admin_device_logs_page_shows_two_step_on_off_statuses(): void
    {
        $admin = $this->createLoginUser(909, [
            'name' => 'Device Logs OTP Admin',
            'email' => 'device-logs-otp-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('device_logs')->insert([
            [
                'ip_address' => '198.51.100.81',
                'username' => 'two-step-on@example.com',
                'browser_os' => 'Desktop | Chrome 145.0.0.0 | Windows 11',
                'two_step_verified' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ip_address' => '198.51.100.82',
                'username' => 'two-step-off@example.com',
                'browser_os' => 'Mobile | Safari 18.0 | iOS 18',
                'two_step_verified' => false,
                'status' => 'deactive',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
        ]);

        $this->actingAs($admin)
            ->get('/admin/device-logs')
            ->assertOk()
            ->assertSee('two-step-on@example.com')
            ->assertSee('two-step-off@example.com')
            ->assertSee('<span class="badge badge-success">On</span>', false)
            ->assertSee('<span class="badge badge-error">Off</span>', false);
    }

    public function test_admin_firebase_config_page_loads_successfully(): void
    {
        $admin = new User();
        $admin->forceFill([
            'id' => 100,
            'name' => 'Firebase Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/admin/firebase-config')
            ->assertOk()
            ->assertSee('Firebase Credentials')
            ->assertSee('Service Account JSON');
    }

    public function test_admin_google_otp_config_page_loads_successfully(): void
    {
        $admin = new User();
        $admin->forceFill([
            'id' => 102,
            'name' => 'Google OTP Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/admin/google-otp-config')
            ->assertOk()
            ->assertSee('Google OTP Configuration')
            ->assertSee('Google Authenticator');
    }

    public function test_admin_can_update_google_otp_settings(): void
    {
        $admin = new User();
        $admin->forceFill([
            'id' => 103,
            'name' => 'Google OTP Settings Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->post('/admin/google-otp-config', [
                'google_otp_enabled' => '1',
                'google_otp_issuer' => 'Codecartel Secure',
            ])
            ->assertRedirect('/admin/google-otp-config');

        $this->assertSame(1, DB::table('homepage_settings')->value('google_otp_enabled'));
        $this->assertSame('Codecartel Secure', DB::table('homepage_settings')->value('google_otp_issuer'));
    }

    public function test_admin_can_enable_and_disable_google_otp_from_admin_profile(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'google_otp_enabled' => true,
            'google_otp_issuer' => 'Codecartel Secure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createLoginUser(1207, [
            'name' => 'Admin OTP Profile',
            'email' => 'admin-otp-profile@example.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Google Authenticator')
            ->assertSee('Manual setup key')
            ->assertSee('Codecartel Secure');

        $secret = app('session.store')->get('admin_google_otp_setup_secret');

        $this->assertNotEmpty($secret);

        $this->post('/admin/profile/google-otp/enable', [
            'otp' => $this->currentGoogleOtpCode($secret),
        ])->assertRedirect('/admin/profile');

        $this->assertSame(1, DB::table('users')->where('id', $admin->id)->value('google_otp_enabled'));
        $this->assertSame($secret, DB::table('users')->where('id', $admin->id)->value('google_otp_secret'));

        $this->post('/admin/profile/google-otp/disable', [
            'disable_pin' => '1234',
        ])->assertRedirect('/admin/profile');

        $this->assertSame(0, DB::table('users')->where('id', $admin->id)->value('google_otp_enabled'));
        $this->assertNull(DB::table('users')->where('id', $admin->id)->value('google_otp_secret'));
    }

    public function test_user_can_enable_and_disable_google_otp_from_dedicated_page(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'google_otp_enabled' => true,
            'google_otp_issuer' => 'Codecartel Secure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createLoginUser(104, [
            'name' => 'OTP Profile User',
            'email' => 'otp-profile@example.com',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('Google Authenticator')
            ->assertDontSee('Manual setup key')
            ->assertDontSee('Codecartel Secure');

        $this->actingAs($user)
            ->get('/profile/google-otp')
            ->assertOk()
            ->assertSee('Google Authenticator')
            ->assertSee('Manual setup key')
            ->assertSee('Codecartel Secure');

        $secret = app('session.store')->get('google_otp_setup_secret');

        $this->assertNotEmpty($secret);

        $this->post('/profile/google-otp/enable', [
            'otp' => $this->currentGoogleOtpCode($secret),
        ])->assertRedirect('/profile/google-otp');

        $this->assertSame(1, DB::table('users')->where('id', $user->id)->value('google_otp_enabled'));
        $this->assertSame($secret, DB::table('users')->where('id', $user->id)->value('google_otp_secret'));

        $this->post('/profile/google-otp/disable', [
            'disable_pin' => '1234',
        ])->assertRedirect('/profile/google-otp');

        $this->assertSame(0, DB::table('users')->where('id', $user->id)->value('google_otp_enabled'));
        $this->assertNull(DB::table('users')->where('id', $user->id)->value('google_otp_secret'));
    }

    public function test_user_api_settings_page_generates_and_shows_api_key(): void
    {
        $user = $this->createLoginUser(1301, [
            'name' => 'API User',
            'email' => 'api-user@example.com',
            'api_key' => null,
        ]);

        $this->actingAs($user)
            ->get('/profile/api')
            ->assertOk()
            ->assertSee('API Settings')
            ->assertDontSee('API Connection Status')
            ->assertDontSee('Pending Manual Approval')
            ->assertDontSee('API Service Controls')
            ->assertDontSee('Save Service Settings')
            ->assertSee('New Web For Api')
            ->assertSee('Domain List')
            ->assertSee('Etross');

        $savedApiKey = DB::table('users')->where('id', $user->id)->value('api_key');

        $this->assertNotEmpty($savedApiKey);
        $this->assertSame(48, strlen($savedApiKey));
    }

    public function test_user_can_update_api_service_toggle_settings(): void
    {
        $user = $this->createLoginUser(1312, [
            'name' => 'API Service Toggle User',
            'email' => 'api-service-toggle-user@example.com',
            'api_key' => 'SERVICETOGGLEAPIKEY1234567890SERVICETOGGLE12',
            'api_access_enabled' => true,
            'api_services' => null,
        ]);

        $this->actingAs($user)
            ->post('/profile/api/services', [
                'services' => ['internet', 'rocket', 'rocket'],
            ])
            ->assertRedirect('/profile/api')
            ->assertSessionHas('success', 'API service settings updated successfully!');

        $this->assertSame(['internet', 'rocket'], $user->fresh()->api_services);

        $this->actingAs($user)
            ->get('/profile/api')
            ->assertOk()
            ->assertSee('Approved')
            ->assertSee('value="internet"', false)
            ->assertSee('value="rocket"', false);
    }

    public function test_user_can_reset_api_key(): void
    {
        $oldApiKey = 'OLDAPIKEY1234567890OLDAPIKEY1234567890OLDAPIKEY';

        $user = $this->createLoginUser(1302, [
            'name' => 'Reset API User',
            'email' => 'reset-api-user@example.com',
            'api_key' => $oldApiKey,
        ]);

        $this->actingAs($user)
            ->post('/profile/api/reset')
            ->assertRedirect('/profile/api');

        $newApiKey = DB::table('users')->where('id', $user->id)->value('api_key');

        $this->assertNotSame($oldApiKey, $newApiKey);
        $this->assertSame(48, strlen($newApiKey));
    }

    public function test_user_can_add_api_domain_and_see_it_in_domain_list(): void
    {
        $user = $this->createLoginUser(1303, [
            'name' => 'Domain API User',
            'email' => 'domain-api-user@example.com',
            'api_key' => 'EXISTINGAPIKEY1234567890EXISTINGAPIKEY1234567890',
        ]);

        $this->actingAs($user)
            ->post('/profile/api/domains', [
                'domain' => 'example.com',
                'provider' => 'Etross',
            ])
            ->assertRedirect('/profile/api');

        $this->assertDatabaseHas('api_domains', [
            'user_id' => $user->id,
            'domain' => 'example.com',
            'provider' => 'Etross',
        ]);

        $this->actingAs($user)
            ->get('/profile/api')
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('Etross');
    }

    public function test_user_can_delete_own_api_domain(): void
    {
        $user = $this->createLoginUser(1304, [
            'name' => 'Delete Domain User',
            'email' => 'delete-domain-user@example.com',
            'api_key' => 'DELETEAPIKEY1234567890DELETEAPIKEY1234567890',
        ]);

        $domainId = DB::table('api_domains')->insertGetId([
            'user_id' => $user->id,
            'domain' => 'delete-me.com',
            'provider' => 'Etross',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete('/profile/api/domains/' . $domainId)
            ->assertRedirect('/profile/api');

        $this->assertDatabaseMissing('api_domains', [
            'id' => $domainId,
            'user_id' => $user->id,
            'domain' => 'delete-me.com',
        ]);
    }

    public function test_user_cannot_delete_another_users_api_domain(): void
    {
        $owner = $this->createLoginUser(1305, [
            'name' => 'Owner Domain User',
            'email' => 'owner-domain-user@example.com',
            'api_key' => 'OWNERAPIKEY1234567890OWNERAPIKEY123456789012',
        ]);

        $otherUser = $this->createLoginUser(1306, [
            'name' => 'Other Domain User',
            'email' => 'other-domain-user@example.com',
            'api_key' => 'OTHERAPIKEY1234567890OTHERAPIKEY123456789012',
        ]);

        $domainId = DB::table('api_domains')->insertGetId([
            'user_id' => $owner->id,
            'domain' => 'owner-only.com',
            'provider' => 'Etross',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->delete('/profile/api/domains/' . $domainId)
            ->assertRedirect('/profile/api')
            ->assertSessionHasErrors('domain');

        $this->assertDatabaseHas('api_domains', [
            'id' => $domainId,
            'user_id' => $owner->id,
            'domain' => 'owner-only.com',
        ]);
    }

    public function test_provider_api_auth_check_rejects_invalid_api_key(): void
    {
        $this->postJson('/api/v1/auth-check', [
            'api_key' => 'INVALID-KEY',
        ])->assertUnauthorized()->assertJson([
            'status' => 'error',
            'message' => 'Invalid API key.',
        ]);
    }

    public function test_provider_api_auth_check_returns_user_and_balances_for_valid_key(): void
    {
        $user = $this->createLoginUser(1307, [
            'name' => 'API Auth User',
            'email' => 'api-auth-user@example.com',
            'api_key' => 'VALIDAPIKEY1234567890VALIDAPIKEY1234567890',
            'api_access_enabled' => true,
            'main_bal' => 150,
            'drive_bal' => 75,
            'bank_bal' => 20,
        ]);

        $this->postJson('/api/v1/auth-check', [
            'api_key' => $user->api_key,
        ])->assertOk()->assertJson([
            'status' => 'success',
            'message' => 'Authenticated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => 'API Auth User',
                'email' => 'api-auth-user@example.com',
            ],
        ]);

        $this->postJson('/api/v1/balance', [
            'api_key' => $user->api_key,
        ])->assertOk()->assertJson([
            'status' => 'success',
            'message' => 'Balance fetched successfully.',
            'balances' => [
                'main_balance' => 150.0,
                'drive_balance' => 75.0,
                'bank_balance' => 20.0,
            ],
        ]);
    }

    public function test_provider_api_auth_check_rejects_when_api_access_is_not_approved(): void
    {
        $user = $this->createLoginUser(1313, [
            'name' => 'API Pending Approval User',
            'email' => 'api-pending-approval-user@example.com',
            'api_key' => 'PENDINGAPIACCESSKEY1234567890PENDINGAPIACCESS',
            'api_access_enabled' => false,
        ]);

        $this->postJson('/api/v1/auth-check', [
            'api_key' => $user->api_key,
        ])->assertForbidden()->assertJson([
            'status' => 'error',
            'message' => 'API access is not approved yet.',
        ]);
    }

    public function test_provider_api_recharge_creates_pending_request_and_deducts_main_balance(): void
    {
        $this->ensureFlexiRequestsTable();

        $user = $this->createLoginUser(1308, [
            'name' => 'API Recharge User',
            'email' => 'api-recharge-user@example.com',
            'api_key' => 'RECHARGEAPIKEY1234567890RECHARGEAPIKEY123456',
            'api_access_enabled' => true,
            'main_bal' => 500,
        ]);

        $response = $this->postJson('/api/v1/recharge', [
            'api_key' => $user->api_key,
            'number' => '01712345678',
            'amount' => 100,
            'type' => 'Prepaid',
        ]);

        $response->assertCreated()->assertJson([
            'status' => 'success',
            'message' => 'Request Received',
            'request_id' => 1,
            'operator' => 'Grameenphone',
            'remaining_balance' => 400.0,
            'balance_type' => 'main_bal',
        ]);

        $this->assertNotEmpty($response->json('trx_id'));
        $this->assertDatabaseHas('flexi_requests', [
            'user_id' => $user->id,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 100,
            'cost' => 100,
            'type' => 'Prepaid',
            'status' => 'pending',
        ]);
        $this->assertSame(400.0, (float) $user->fresh()->main_bal);
    }

    public function test_provider_api_recharge_rejects_when_main_balance_is_insufficient(): void
    {
        $this->ensureFlexiRequestsTable();

        $user = $this->createLoginUser(1309, [
            'name' => 'API Low Balance User',
            'email' => 'api-low-balance-user@example.com',
            'api_key' => 'LOWBALAPIKEY1234567890LOWBALAPIKEY123456789',
            'api_access_enabled' => true,
            'main_bal' => 40,
        ]);

        $this->postJson('/api/v1/recharge', [
            'api_key' => $user->api_key,
            'number' => '01812345678',
            'amount' => 100,
            'type' => 'Prepaid',
        ])->assertStatus(422)->assertJson([
            'status' => 'error',
            'message' => 'Insufficient main balance.',
        ]);

        $this->assertDatabaseCount('flexi_requests', 0);
        $this->assertSame(40.0, (float) $user->fresh()->main_bal);
    }

    public function test_provider_api_recharge_rejects_when_service_is_disabled(): void
    {
        $this->ensureFlexiRequestsTable();

        $user = $this->createLoginUser(1314, [
            'name' => 'API Disabled Recharge User',
            'email' => 'api-disabled-recharge-user@example.com',
            'api_key' => 'DISABLEDRECHARGEKEY1234567890DISABLEDRECHARGE',
            'api_access_enabled' => true,
            'api_services' => ['drive'],
            'main_bal' => 500,
        ]);

        $this->postJson('/api/v1/recharge', [
            'api_key' => $user->api_key,
            'number' => '01712345678',
            'amount' => 100,
            'type' => 'Prepaid',
        ])->assertForbidden()->assertJson([
            'status' => 'error',
            'message' => 'Flexiload Recharge API service is disabled.',
        ]);

        $this->assertDatabaseCount('flexi_requests', 0);
        $this->assertSame(500.0, (float) $user->fresh()->main_bal);
    }

    public function test_provider_api_drive_creates_pending_request_and_deducts_drive_balance(): void
    {
        $this->ensureProviderApiDriveTables();

        DB::table('brandings')->insert([
            'id' => 1,
            'drive_balance' => 'on',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createLoginUser(1310, [
            'name' => 'API Drive User',
            'email' => 'api-drive-user@example.com',
            'api_key' => 'DRIVEAPIKEY1234567890DRIVEAPIKEY1234567890',
            'api_access_enabled' => true,
            'drive_bal' => 300,
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'API Drive Package',
            'price' => 200,
            'commission' => 20,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/drive', [
            'api_key' => $user->api_key,
            'package_id' => $packageId,
            'mobile' => '01712345678',
        ]);

        $response->assertCreated()->assertJson([
            'status' => 'success',
            'message' => 'Request Received',
            'request_id' => 1,
            'remaining_balance' => 120.0,
            'balance_type' => 'drive_bal',
        ]);

        $this->assertNotEmpty($response->json('trx_id'));
        $this->assertDatabaseHas('drive_requests', [
            'user_id' => $user->id,
            'package_id' => $packageId,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 180,
            'status' => 'pending',
            'balance_type' => 'drive_bal',
        ]);
        $this->assertSame(120.0, (float) $user->fresh()->drive_bal);
    }

    public function test_provider_api_drive_uses_main_balance_when_provider_drive_balance_is_off_even_if_same_billing_route_matches(): void
    {
        $this->ensureProviderApiDriveTables();

        DB::table('brandings')->insert([
            'id' => 1,
            'drive_balance' => 'off',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('apis')->insert([
            'id' => 61,
            'title' => 'Same Billing Route API',
            'user_id' => 'route-sb-61',
            'api_key' => 'route-sb-key-61',
            'provider' => 'same billing',
            'api_url' => 'https://same-billing-route.example.test',
            'status' => 'active',
            'balance' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('api_routes')->insert([
            'title' => 'Same Billing Drive Route',
            'module_type' => 'api',
            'module_name' => 'Same Billing Route API',
            'api_id' => 61,
            'service' => 'drive',
            'code' => 'Gp',
            'priority' => 1,
            'prefix' => '017',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createLoginUser(1311, [
            'name' => 'API Same Billing Drive User',
            'email' => 'api-same-billing-drive-user@example.com',
            'api_key' => 'SAMEBILLINGDRIVEKEY1234567890SAMEBILLINGDRIV',
            'api_access_enabled' => true,
            'main_bal' => 300,
            'drive_bal' => 300,
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Same Billing Drive Package',
            'price' => 200,
            'commission' => 20,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/drive', [
            'api_key' => $user->api_key,
            'package_id' => $packageId,
            'mobile' => '01712345678',
        ]);

        $response->assertCreated()->assertJson([
            'status' => 'success',
            'message' => 'Request Received',
            'request_id' => 1,
            'remaining_balance' => 120.0,
            'balance_type' => 'main_bal',
        ]);

        $this->assertDatabaseHas('drive_requests', [
            'user_id' => $user->id,
            'package_id' => $packageId,
            'balance_type' => 'main_bal',
        ]);
        $this->assertSame(120.0, (float) $user->fresh()->main_bal);
        $this->assertSame(300.0, (float) $user->fresh()->drive_bal);
    }

    public function test_provider_api_internet_creates_pending_request_and_deducts_main_balance(): void
    {
        $this->ensureProviderApiInternetTables();

        $user = $this->createLoginUser(1315, [
            'name' => 'API Internet User',
            'email' => 'api-internet-user@example.com',
            'api_key' => 'INTERNETAPIKEY1234567890INTERNETAPIKEY123456',
            'api_access_enabled' => true,
            'api_services' => ['internet'],
            'main_bal' => 500,
        ]);

        $packageId = DB::table('regular_packages')->insertGetId([
            'operator' => 'Robi',
            'name' => 'API Internet Package',
            'price' => 250,
            'commission' => 30,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/internet', [
            'api_key' => $user->api_key,
            'package_id' => $packageId,
            'mobile' => '01812345678',
        ]);

        $response->assertCreated()->assertJson([
            'status' => 'success',
            'message' => 'Request Received',
            'request_id' => 1,
            'remaining_balance' => 280.0,
            'balance_type' => 'main_bal',
        ]);

        $this->assertNotEmpty($response->json('trx_id'));
        $this->assertStringStartsWith('INT-1-', $response->json('trx_id'));
        $this->assertDatabaseHas('regular_requests', [
            'user_id' => $user->id,
            'package_id' => $packageId,
            'operator' => 'Robi',
            'mobile' => '01812345678',
            'amount' => 220,
            'status' => 'pending',
        ]);
        $this->assertSame(280.0, (float) $user->fresh()->main_bal);
    }

    public function test_provider_api_rejects_request_from_unlisted_domain_when_whitelist_exists(): void
    {
        $user = $this->createLoginUser(1311, [
            'name' => 'API Domain User',
            'email' => 'api-domain-user@example.com',
            'api_key' => 'DOMAINAPIKEY1234567890DOMAINAPIKEY123456789',
            'api_access_enabled' => true,
        ]);

        DB::table('api_domains')->insert([
            'user_id' => $user->id,
            'domain' => 'allowed-example.com',
            'provider' => 'Etross',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders([
            'X-Client-Domain' => 'blocked-example.com',
        ])->postJson('/api/v1/auth-check', [
            'api_key' => $user->api_key,
        ])->assertForbidden()->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized client domain.',
        ]);

        $this->withHeaders([
            'X-Client-Domain' => 'allowed-example.com',
        ])->postJson('/api/v1/auth-check', [
            'api_key' => $user->api_key,
        ])->assertOk()->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_provider_api_bkash_nagad_rocket_and_upay_create_pending_manual_payment_requests(): void
    {
        $this->ensureManualPaymentRequestsTable();

        DB::table('brandings')->insert([
            'id' => 1,
            'bkash' => '01700000000',
            'nagad' => '01800000000',
            'rocket' => '01900000000',
            'upay' => '01600000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createLoginUser(1316, [
            'name' => 'API Manual Payment User',
            'email' => 'api-manual-payment-user@example.com',
            'api_key' => 'MANUALPAYAPIKEY1234567890MANUALPAYAPIKEY1234',
            'api_access_enabled' => true,
            'api_services' => ['bkash', 'nagad', 'rocket', 'upay'],
        ]);

        foreach (
            [
                ['endpoint' => '/api/v1/bkash', 'method' => 'Bkash', 'trx' => 'BKASH-TRX-001', 'amount' => 120],
                ['endpoint' => '/api/v1/nagad', 'method' => 'Nagad', 'trx' => 'NAGAD-TRX-001', 'amount' => 130],
                ['endpoint' => '/api/v1/rocket', 'method' => 'Rocket', 'trx' => 'ROCKET-TRX-001', 'amount' => 140],
                ['endpoint' => '/api/v1/upay', 'method' => 'Upay', 'trx' => 'UPAY-TRX-001', 'amount' => 150],
            ] as $index => $case
        ) {
            $response = $this->postJson($case['endpoint'], [
                'api_key' => $user->api_key,
                'sender_number' => '01712345678',
                'transaction_id' => $case['trx'],
                'amount' => $case['amount'],
                'note' => 'API manual payment request',
            ]);

            $response->assertCreated()->assertJson([
                'status' => 'success',
                'message' => 'Request Received',
                'method' => $case['method'],
                'trx_id' => $case['trx'],
                'request_id' => $index + 1,
            ]);

            $this->assertDatabaseHas('manual_payment_requests', [
                'user_id' => $user->id,
                'method' => $case['method'],
                'sender_number' => '01712345678',
                'transaction_id' => $case['trx'],
                'amount' => $case['amount'],
                'status' => 'pending',
            ]);
        }

        $this->assertSame(4, DB::table('manual_payment_requests')->count());
    }

    public function test_user_login_requires_google_otp_when_enabled_for_account(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'google_otp_enabled' => true,
            'google_otp_issuer' => 'Codecartel Secure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createLoginUser(105, [
            'name' => 'OTP Required User',
            'email' => 'otp-required@example.com',
            'google_otp_secret' => 'JBSWY3DPEHPK3PXP',
            'google_otp_enabled' => true,
            'google_otp_confirmed_at' => now(),
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'otp-required@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ])
            ->assertRedirect(route('login.otp.show'));

        $this->assertGuest();
    }

    public function test_user_login_succeeds_after_valid_google_otp_on_second_step(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'google_otp_enabled' => true,
            'google_otp_issuer' => 'Codecartel Secure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secret = 'JBSWY3DPEHPK3PXP';
        $user = $this->createLoginUser(106, [
            'name' => 'OTP Valid User',
            'email' => 'otp-valid@example.com',
            'google_otp_secret' => $secret,
            'google_otp_enabled' => true,
            'google_otp_confirmed_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'otp-valid@example.com',
            'password' => 'secret123',
            'pin' => '1234',
        ])->assertRedirect(route('login.otp.show'));

        $response = $this->post(route('login.otp.verify'), [
            'otp' => $this->currentGoogleOtpCode($secret),
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_login_without_google_otp_enabled_goes_directly_to_dashboard(): void
    {
        $user = $this->createLoginUser(107, [
            'name' => 'No OTP User',
            'email' => 'no-otp-user@example.com',
            'google_otp_enabled' => false,
            'google_otp_secret' => null,
            'google_otp_confirmed_at' => null,
        ]);

        $response = $this->post('/login', [
            'email' => 'no-otp-user@example.com',
            'password' => 'secret123',
            'pin' => '1234',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_requires_google_otp_when_enabled_for_account(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'google_otp_enabled' => true,
            'google_otp_issuer' => 'Codecartel Secure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createLoginUser(1208, [
            'name' => 'OTP Required Admin',
            'email' => 'otp-required-admin@example.com',
            'is_admin' => true,
            'google_otp_secret' => 'JBSWY3DPEHPK3PXP',
            'google_otp_enabled' => true,
            'google_otp_confirmed_at' => now(),
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'otp-required-admin@example.com',
                'password' => 'secret123',
                'pin' => '1234',
            ])
            ->assertRedirect(route('admin.login.otp.show'));

        $this->assertGuest();
    }

    public function test_admin_login_succeeds_after_valid_google_otp_on_second_step(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'google_otp_enabled' => true,
            'google_otp_issuer' => 'Codecartel Secure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secret = 'JBSWY3DPEHPK3PXP';
        $admin = $this->createLoginUser(1209, [
            'name' => 'OTP Valid Admin',
            'email' => 'otp-valid-admin@example.com',
            'is_admin' => true,
            'google_otp_secret' => $secret,
            'google_otp_enabled' => true,
            'google_otp_confirmed_at' => now(),
        ]);

        $this->post('/admin/login', [
            'email' => 'otp-valid-admin@example.com',
            'password' => 'secret123',
            'pin' => '1234',
        ])->assertRedirect(route('admin.login.otp.show'));

        $response = $this->post(route('admin.login.otp.verify'), [
            'otp' => $this->currentGoogleOtpCode($secret),
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_login_without_google_otp_enabled_goes_directly_to_dashboard(): void
    {
        $admin = $this->createLoginUser(1210, [
            'name' => 'No OTP Admin',
            'email' => 'no-otp-admin@example.com',
            'is_admin' => true,
            'google_otp_enabled' => false,
            'google_otp_secret' => null,
            'google_otp_confirmed_at' => null,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'no-otp-admin@example.com',
            'password' => 'secret123',
            'pin' => '1234',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_payment_gateway_page_loads_successfully(): void
    {
        DB::table('brandings')->insert([
            'bkash' => '01700000000',
            'rocket' => '01800000000',
            'nagad' => '01900000000',
            'upay' => '01600000000',
            'sslcommerz_store_id' => 'ssl-test-store',
            'sslcommerz_store_password' => 'ssl-test-pass',
            'sslcommerz_mode' => 'sandbox',
            'amarpay_store_id' => 'amar-store',
            'amarpay_signature_key' => 'amar-signature',
            'amarpay_mode' => 'live',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = new User();
        $admin->forceFill([
            'id' => 101,
            'name' => 'Gateway Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/admin/payment-gateway')
            ->assertOk()
            ->assertSee('Payment Gateway Settings')
            ->assertSee('01700000000')
            ->assertSee('01900000000')
            ->assertSee('ssl-test-store')
            ->assertSee('amar-store');
    }

    public function test_admin_payment_gateway_settings_update_stores_gateway_credentials(): void
    {
        $admin = new User();
        $admin->forceFill([
            'id' => 102,
            'name' => 'Gateway Settings Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->post('/admin/payment-gateway/update', [
                'bkash' => '01711111111',
                'rocket' => '01811111111',
                'nagad' => '01911111111',
                'upay' => '01611111111',
                'sslcommerz_store_id' => 'updated-ssl-store',
                'sslcommerz_store_password' => 'updated-ssl-password',
                'sslcommerz_mode' => 'live',
                'amarpay_store_id' => 'updated-amar-store',
                'amarpay_signature_key' => 'updated-amar-signature',
                'amarpay_mode' => 'sandbox',
            ])
            ->assertRedirect('/admin/payment-gateway');

        $this->assertSame('updated-ssl-store', DB::table('brandings')->where('id', 1)->value('sslcommerz_store_id'));
        $this->assertSame('live', DB::table('brandings')->where('id', 1)->value('sslcommerz_mode'));
        $this->assertSame('updated-amar-store', DB::table('brandings')->where('id', 1)->value('amarpay_store_id'));
        $this->assertSame('sandbox', DB::table('brandings')->where('id', 1)->value('amarpay_mode'));
    }

    public function test_admin_payment_gateway_settings_update_skips_missing_gateway_columns_without_sql_error(): void
    {
        Schema::dropIfExists('brandings');

        Schema::create('brandings', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name')->nullable();
            $table->string('bkash')->nullable();
            $table->string('rocket')->nullable();
            $table->string('nagad')->nullable();
            $table->string('upay')->nullable();
            $table->timestamps();
        });

        $admin = new User();
        $admin->forceFill([
            'id' => 112,
            'name' => 'Gateway Legacy Schema Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->post('/admin/payment-gateway/update', [
                'bkash' => '01722222222',
                'rocket' => '01822222222',
                'nagad' => '01922222222',
                'upay' => '01622222222',
                'sslcommerz_store_id' => 'legacy-ssl-store',
                'sslcommerz_store_password' => 'legacy-ssl-password',
                'sslcommerz_mode' => 'sandbox',
                'amarpay_store_id' => 'legacy-amar-store',
                'amarpay_signature_key' => 'legacy-amar-signature',
                'amarpay_mode' => 'live',
            ])
            ->assertRedirect('/admin/payment-gateway')
            ->assertSessionHas('warning');

        $this->assertSame('01722222222', DB::table('brandings')->where('id', 1)->value('bkash'));
        $this->assertSame('01822222222', DB::table('brandings')->where('id', 1)->value('rocket'));
        $this->assertFalse(Schema::hasColumn('brandings', 'sslcommerz_store_id'));
    }

    public function test_admin_api_settings_page_loads_user_controls_without_provider_docs(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createLoginUser(1301, [
            'name' => 'API Settings Admin',
            'email' => 'api-settings-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(1302, [
            'name' => 'API Client User',
            'email' => 'api-client-user@example.com',
            'api_key' => 'client-key-1302',
            'api_services' => ['drive', 'internet'],
        ]);

        DB::table('api_domains')->insert([
            'user_id' => $user->id,
            'domain' => 'client.example.com',
            'provider' => 'Etross',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('apis')->insert([
            'id' => 101,
            'title' => 'Same Billing Main',
            'user_id' => 'sb-101',
            'api_key' => 'same-billing-key',
            'provider' => 'same billing',
            'api_url' => 'https://provider.example.test/balance',
            'status' => 'active',
            'balance' => 1550.75,
            'main_balance' => 1200.50,
            'drive_balance' => 210.25,
            'bank_balance' => 140.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('apis')->insert([
            'id' => 102,
            'title' => 'Pending Approval API',
            'user_id' => 'sb-102',
            'api_key' => 'pending-approval-key',
            'provider' => 'same billing',
            'api_url' => 'https://provider.example.test/pending-balance',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('apis')->insert([
            'id' => 103,
            'title' => 'Inactive API',
            'user_id' => 'sb-103',
            'api_key' => 'inactive-api-key',
            'provider' => 'same billing',
            'api_url' => 'https://provider.example.test/inactive-balance',
            'status' => 'deactive',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('api_connection_approvals')->insert([
            'api_id' => 101,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('api_connection_approvals')->insert([
            'api_id' => 102,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/api-settings')
            ->assertOk()
            ->assertSee('API Connection Settings')
            ->assertSee('3 Saved APIs')
            ->assertSeeInOrder(['Saved APIs', '3', 'Active APIs', '1', 'API Connection Management'])
            ->assertSee('API Connection Management')
            ->assertSee('Add API')
            ->assertSee('API Information')
            ->assertSee('Same Billing Main')
            ->assertSee('sb-101')
            ->assertSee('same billing')
            ->assertSee('approval')
            ->assertSee('main balance')
            ->assertSee('drive balance')
            ->assertSee('bank balance')
            ->assertSee('৳ 1,200.50')
            ->assertSee('৳ 210.25')
            ->assertSee('৳ 140.00')
            ->assertDontSee('৳ 1,550.75')
            ->assertSee('Balance check')
            ->assertDontSee('Provider API Management')
            ->assertDontSee('User API Controls')
            ->assertDontSee('Simple Provider API Documentation')
            ->assertDontSee('POST /api/v1/auth-check');
    }

    public function test_admin_can_store_api_connection_from_api_settings_page(): void
    {
        $admin = $this->createLoginUser(1311, [
            'name' => 'API Connection Admin',
            'email' => 'api-connection-admin@example.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->post('/api-settings/connections', [
                'title' => 'Ecare Main',
                'user_id' => 'ecare-501',
                'api_key' => 'ecare-secret-key',
                'provider' => 'Ecare Technology',
                'api_url' => 'https://ecare.example.test/balance',
                'status' => 'active',
            ])
            ->assertRedirect('/api-settings')
            ->assertSessionHas('success', 'API connection saved successfully!');

        $this->assertSame('ecare-secret-key', DB::table('apis')->where('user_id', 'ecare-501')->value('api_key'));
        $this->assertSame('Ecare Technology', DB::table('apis')->where('user_id', 'ecare-501')->value('provider'));
        $this->assertSame('active', DB::table('apis')->where('user_id', 'ecare-501')->value('status'));
    }

    public function test_admin_can_update_and_delete_api_connection(): void
    {
        $admin = $this->createLoginUser(1312, [
            'name' => 'API Modify Admin',
            'email' => 'api-modify-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('apis')->insert([
            'id' => 11,
            'title' => 'Old API',
            'user_id' => 'old-11',
            'api_key' => 'old-key',
            'provider' => 'same billing',
            'api_url' => 'https://old.example.test/api',
            'status' => 'active',
            'balance' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put('/api-settings/connections/11', [
                'title' => 'Updated API',
                'user_id' => 'updated-11',
                'api_key' => 'updated-key',
                'provider' => 'Ecare Technology',
                'api_url' => 'https://updated.example.test/api',
                'status' => 'deactive',
            ])
            ->assertRedirect('/api-settings')
            ->assertSessionHas('success', 'API connection updated successfully!');

        $this->assertSame('Updated API', DB::table('apis')->where('id', 11)->value('title'));
        $this->assertSame('deactive', DB::table('apis')->where('id', 11)->value('status'));

        $this->actingAs($admin)
            ->post('/api-settings/connections/11/balance-check')
            ->assertRedirect('/api-settings')
            ->assertSessionHas('error', 'This API connection is deactive. Please active it first.');

        $this->actingAs($admin)
            ->delete('/api-settings/connections/11')
            ->assertRedirect('/api-settings')
            ->assertSessionHas('success', 'API connection deleted successfully!');

        $this->assertNull(DB::table('apis')->where('id', 11)->first());
    }

    public function test_admin_can_balance_check_active_api_connection(): void
    {
        $admin = $this->createLoginUser(1313, [
            'name' => 'API Balance Admin',
            'email' => 'api-balance-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('apis')->insert([
            'id' => 12,
            'title' => 'Balance API',
            'user_id' => 'balance-12',
            'api_key' => 'balance-key',
            'provider' => 'same billing',
            'api_url' => 'https://provider.example.test/balance',
            'status' => 'active',
            'balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://provider.example.test/balance' => Http::response([
                'status' => 'success',
                'balance' => 987.65,
                'main_balance' => 700.50,
                'drive_balance' => 200.10,
                'bank_balance' => 87.05,
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post('/api-settings/connections/12/balance-check')
            ->assertRedirect('/api-settings')
            ->assertSessionHas('success', 'Balance checked successfully for Balance API.');

        $this->assertSame('987.65', number_format((float) DB::table('apis')->where('id', 12)->value('balance'), 2, '.', ''));
        $this->assertSame('700.50', number_format((float) DB::table('apis')->where('id', 12)->value('main_balance'), 2, '.', ''));
        $this->assertSame('200.10', number_format((float) DB::table('apis')->where('id', 12)->value('drive_balance'), 2, '.', ''));
        $this->assertSame('87.05', number_format((float) DB::table('apis')->where('id', 12)->value('bank_balance'), 2, '.', ''));
        $this->assertSame(1, (int) DB::table('api_connection_approvals')->where('api_id', 12)->value('status'));
    }

    public function test_admin_balance_check_rewrites_known_provider_endpoint_and_sends_client_domain(): void
    {
        config(['app.url' => 'https://codecarteltelecom.example.test']);

        $admin = $this->createLoginUser(1316, [
            'name' => 'API Balance Domain Admin',
            'email' => 'api-balance-domain-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('apis')->insert([
            'id' => 14,
            'title' => 'Auth Check API',
            'user_id' => 'auth-check-14',
            'api_key' => 'auth-check-key',
            'provider' => 'Ecare Technology',
            'api_url' => 'https://provider.example.test/auth-check',
            'status' => 'active',
            'balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://provider.example.test/auth-check' => Http::response([], 404),
            'https://provider.example.test/balance' => Http::response([
                'status' => 'success',
                'balance' => 123.45,
            ], 200),
        ]);

        $this->actingAs($admin)
            ->withServerVariables(['HTTP_HOST' => 'codecarteltelecom.example.test'])
            ->post('/api-settings/connections/14/balance-check')
            ->assertRedirect('/api-settings')
            ->assertSessionHas('success', 'Balance checked successfully for Auth Check API.');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'https://provider.example.test/balance'
                && (($request->header('X-Client-Domain')[0] ?? null) === 'codecarteltelecom.example.test')
                && (($request->data()['domain'] ?? null) === 'codecarteltelecom.example.test');
        });

        $this->assertSame('123.45', number_format((float) DB::table('apis')->where('id', 14)->value('balance'), 2, '.', ''));
        $this->assertSame(1, (int) DB::table('api_connection_approvals')->where('api_id', 14)->value('status'));
    }

    public function test_admin_balance_check_clears_snapshot_and_sets_approval_deactive_when_provider_response_fails(): void
    {
        $admin = $this->createLoginUser(1315, [
            'name' => 'API Balance Failure Admin',
            'email' => 'api-balance-failure-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('apis')->insert([
            'id' => 13,
            'title' => 'Broken Balance API',
            'user_id' => 'broken-13',
            'api_key' => 'broken-key',
            'provider' => 'same billing',
            'api_url' => 'https://provider.example.test/broken-balance',
            'status' => 'active',
            'balance' => 500,
            'main_balance' => 300,
            'drive_balance' => 120,
            'bank_balance' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('api_connection_approvals')->insert([
            'api_id' => 13,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://provider.example.test/broken-balance' => Http::response([
                'status' => 'error',
                'message' => 'Provider rejected credentials.',
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post('/api-settings/connections/13/balance-check')
            ->assertRedirect('/api-settings')
            ->assertSessionHas('error', 'Provider rejected credentials.');

        $this->assertSame('0.00', number_format((float) DB::table('apis')->where('id', 13)->value('balance'), 2, '.', ''));
        $this->assertNull(DB::table('apis')->where('id', 13)->value('main_balance'));
        $this->assertNull(DB::table('apis')->where('id', 13)->value('drive_balance'));
        $this->assertNull(DB::table('apis')->where('id', 13)->value('bank_balance'));
        $this->assertSame(0, (int) DB::table('api_connection_approvals')->where('api_id', 13)->value('status'));

        $this->actingAs($admin)
            ->get('/api-settings')
            ->assertOk()
            ->assertSee('Broken Balance API')
            ->assertSee('deactive')
            ->assertSee('—');
    }

    public function test_connection_route_button_redirects_to_api_route_management_page(): void
    {
        $admin = $this->createLoginUser(1314, [
            'name' => 'API Route Admin',
            'email' => 'api-route-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('apis')->insert([
            'id' => 21,
            'title' => 'Route Gateway One',
            'user_id' => 'route-21',
            'api_key' => 'route-key-21',
            'provider' => 'same billing',
            'api_url' => 'https://route-one.example.test',
            'status' => 'deactive',
            'balance' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/api-settings/connections/21/route')
            ->assertRedirect('/api-settings/routes?connection=21');
    }

    public function test_admin_api_route_management_page_loads_with_requested_form_options(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createLoginUser(1315, [
            'name' => 'Routing Page Admin',
            'email' => 'routing-page-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('apis')->insert([
            [
                'id' => 31,
                'title' => 'Same Billing Main',
                'user_id' => 'sb-31',
                'api_key' => 'sb-key-31',
                'provider' => 'same billing',
                'api_url' => 'https://same-billing.example.test',
                'status' => 'active',
                'balance' => 150,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 32,
                'title' => 'Ecare Backup',
                'user_id' => 'ecare-32',
                'api_key' => 'ecare-key-32',
                'provider' => 'Ecare Technology',
                'api_url' => 'https://ecare.example.test',
                'status' => 'active',
                'balance' => 250,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('api_routes')->insert([
            'title' => 'Primary Flexi Route',
            'module_type' => 'api',
            'module_name' => 'Same Billing Main',
            'api_id' => 31,
            'service' => 'recharge',
            'code' => 'Gp',
            'priority' => 1,
            'prefix' => '017',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/api-settings/routes?connection=31')
            ->assertOk()
            ->assertSee('API Routing Management')
            ->assertSee('Add Route')
            ->assertSee('Routing Information')
            ->assertSee('manul System')
            ->assertSee('Same Billing Main')
            ->assertSee('Ecare Backup')
            ->assertSee('All service')
            ->assertSee('Flexiload')
            ->assertSee('Internet pack')
            ->assertSee('upay')
            ->assertSee('All Product Code')
            ->assertSee('Gp')
            ->assertSee('SK')
            ->assertSee('Primary Flexi Route')
            ->assertSee('017');
    }

    public function test_admin_can_store_update_and_delete_api_route(): void
    {
        $admin = $this->createLoginUser(1316, [
            'name' => 'Routing Save Admin',
            'email' => 'routing-save-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('apis')->insert([
            'id' => 41,
            'title' => 'Route Module API',
            'user_id' => 'route-41',
            'api_key' => 'route-key-41',
            'provider' => 'same billing',
            'api_url' => 'https://route-module.example.test',
            'status' => 'active',
            'balance' => 400,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/api-settings/routes', [
                'title' => 'Manual Backup Route',
                'module' => 'api:41',
                'service' => 'rocket',
                'code' => 'RB',
                'priority' => 2,
                'prefix' => '018',
                'status' => 'active',
                'context_connection_id' => 41,
            ])
            ->assertRedirect('/api-settings/routes?connection=41')
            ->assertSessionHas('success', 'API route saved successfully!');

        $routeId = DB::table('api_routes')->where('title', 'Manual Backup Route')->value('id');

        $this->assertNotNull($routeId);
        $this->assertSame('Route Module API', DB::table('api_routes')->where('id', $routeId)->value('module_name'));
        $this->assertSame('rocket', DB::table('api_routes')->where('id', $routeId)->value('service'));
        $this->assertSame('RB', DB::table('api_routes')->where('id', $routeId)->value('code'));

        $this->actingAs($admin)
            ->put('/api-settings/routes/' . $routeId, [
                'title' => 'Manual System Route',
                'module' => 'manual',
                'service' => 'all',
                'code' => 'all',
                'priority' => 5,
                'prefix' => '019',
                'status' => 'deactive',
                'context_connection_id' => 41,
            ])
            ->assertRedirect('/api-settings/routes?connection=41')
            ->assertSessionHas('success', 'API route updated successfully!');

        $this->assertSame('manul System', DB::table('api_routes')->where('id', $routeId)->value('module_name'));
        $this->assertSame('manual', DB::table('api_routes')->where('id', $routeId)->value('module_type'));
        $this->assertSame('deactive', DB::table('api_routes')->where('id', $routeId)->value('status'));

        $this->actingAs($admin)
            ->delete('/api-settings/routes/' . $routeId, [
                'context_connection_id' => 41,
            ])
            ->assertRedirect('/api-settings/routes?connection=41')
            ->assertSessionHas('success', 'API route deleted successfully!');

        $this->assertNull(DB::table('api_routes')->where('id', $routeId)->first());
    }

    public function test_admin_can_update_user_api_approval_and_service_settings(): void
    {
        $admin = $this->createLoginUser(1303, [
            'name' => 'API Update Admin',
            'email' => 'api-update-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(1304, [
            'name' => 'API Update User',
            'email' => 'api-update-user@example.com',
            'api_access_enabled' => false,
            'api_services' => ['recharge'],
        ]);

        $this->actingAs($admin)
            ->post('/api-settings/' . $user->id, [
                'api_access_enabled' => '1',
                'services' => ['drive', 'internet', 'rocket'],
            ])
            ->assertRedirect('/api-settings')
            ->assertSessionHas('success', 'API settings updated successfully!');

        $freshUser = $user->fresh();

        $this->assertTrue((bool) $freshUser->api_access_enabled);
        $this->assertSame(['drive', 'internet', 'rocket'], $freshUser->enabledApiServices());
    }

    public function test_admin_api_settings_update_cannot_target_admin_accounts(): void
    {
        $admin = $this->createLoginUser(1305, [
            'name' => 'Main API Admin',
            'email' => 'main-api-admin@example.com',
            'is_admin' => true,
        ]);

        $targetAdmin = $this->createLoginUser(1306, [
            'name' => 'Protected Admin',
            'email' => 'protected-admin@example.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->from('/api-settings')
            ->post('/api-settings/' . $targetAdmin->id, [
                'api_access_enabled' => '1',
                'services' => ['drive'],
            ])
            ->assertRedirect('/api-settings')
            ->assertSessionHas('error', 'Admin accounts are not managed from API settings.');

        $freshTargetAdmin = $targetAdmin->fresh();

        $this->assertFalse((bool) $freshTargetAdmin->api_access_enabled);
        $this->assertNull($freshTargetAdmin->getRawOriginal('api_services'));
    }

    public function test_admin_api_settings_page_loads_when_api_schema_columns_are_missing(): void
    {
        DB::table('homepage_settings')->insert([
            'company_name' => 'Codecartel Telecom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createLoginUser(1307, [
            'name' => 'Schema Safe Admin',
            'email' => 'schema-safe-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(1308, [
            'name' => 'Schema Warning User',
            'email' => 'schema-warning-user@example.com',
            'api_key' => 'schema-warning-key',
            'api_access_enabled' => true,
            'api_services' => ['drive'],
        ]);

        DB::table('api_domains')->insert([
            'user_id' => $user->id,
            'domain' => 'schema-client.example.com',
            'provider' => 'Etross',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_access_enabled', 'api_services']);
        });

        Schema::dropIfExists('api_domains');

        $this->actingAs($admin)
            ->get('/api-settings')
            ->assertOk()
            ->assertSee('API Connection Settings')
            ->assertSee('API settings schema fully ready noy')
            ->assertSee('users.api_access_enabled column missing')
            ->assertSee('users.api_services column missing')
            ->assertSee('api_domains table missing')
            ->assertDontSee('User API Controls');
    }

    public function test_admin_api_settings_update_shows_error_when_api_access_columns_are_missing(): void
    {
        $admin = $this->createLoginUser(1309, [
            'name' => 'Schema Update Admin',
            'email' => 'schema-update-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(1310, [
            'name' => 'Schema Update User',
            'email' => 'schema-update-user@example.com',
            'api_access_enabled' => false,
            'api_services' => ['recharge'],
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_access_enabled', 'api_services']);
        });

        $this->actingAs($admin)
            ->from('/api-settings')
            ->post('/api-settings/' . $user->id, [
                'api_access_enabled' => '1',
                'services' => ['drive', 'internet'],
            ])
            ->assertRedirect('/api-settings')
            ->assertSessionHas('error', 'API access columns are missing in the users table. Please run php artisan migrate first.');
    }

    public function test_authenticated_user_can_open_add_balance_page_and_see_manual_methods(): void
    {
        DB::table('brandings')->insert([
            'bkash' => '01700000000',
            'rocket' => '01800000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->forceFill([
            'id' => 103,
            'name' => 'Balance User',
            'is_admin' => 0,
            'permissions' => json_encode(['add_balance']),
        ]);

        $this->actingAs($user)
            ->get('/add-balance')
            ->assertOk()
            ->assertSee('Add Balance')
            ->assertSee('01700000000')
            ->assertSee('01800000000');
    }

    public function test_authenticated_user_can_submit_manual_payment_request_and_see_it_in_recent_history(): void
    {
        $this->ensureManualPaymentRequestsTable();

        DB::table('brandings')->insert([
            'bkash' => '01700000000',
            'nagad' => '01900000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createLoginUser(601, [
            'name' => 'Manual Request User',
            'email' => 'manual-request-user@example.com',
        ]);

        $this->actingAs($user)
            ->post('/add-balance', [
                'method' => 'Bkash',
                'sender_number' => '01712345678',
                'transaction_id' => 'MANUAL-TX-001',
                'amount' => '250',
                'note' => 'Paid from personal account',
            ])
            ->assertRedirect('/add-balance')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('manual_payment_requests', [
            'user_id' => $user->id,
            'method' => 'Bkash',
            'sender_number' => '01712345678',
            'transaction_id' => 'MANUAL-TX-001',
            'amount' => 250,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get('/add-balance')
            ->assertOk()
            ->assertSee('Submit Payment Request')
            ->assertSee('Recent Requests')
            ->assertSee('MANUAL-TX-001')
            ->assertSee('Pending');
    }

    public function test_manual_payment_request_shows_in_user_and_admin_pending_lists(): void
    {
        $this->ensureManualPaymentRequestsTable();

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        $admin = $this->createLoginUser(602, [
            'name' => 'Manual Pending Admin',
            'email' => 'manual-pending-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(603, [
            'name' => 'Manual Pending User',
            'email' => 'manual-pending-user@example.com',
        ]);

        DB::table('manual_payment_requests')->insert([
            'id' => 1,
            'user_id' => $user->id,
            'method' => 'Bkash',
            'sender_number' => '01712345678',
            'transaction_id' => 'BKASH-PEND-001',
            'amount' => 500,
            'note' => 'Pending payment',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/my-pending-requests')
            ->assertOk()
            ->assertSee('My Pending Requests')
            ->assertSee('Balance Add')
            ->assertSee('Bkash')
            ->assertSee('BKASH-PEND-001')
            ->assertSee('01712345678');

        $this->actingAs($admin)
            ->get('/admin/pending-drive-requests?service=bkash')
            ->assertOk()
            ->assertSee('Manual Pending User')
            ->assertSee('Bkash')
            ->assertSee('BKASH-PEND-001')
            ->assertSee('01712345678')
            ->assertSee('/admin/manual-payment-requests/1/approve', false)
            ->assertSee('/admin/manual-payment-requests/1/failed', false)
            ->assertSee('/admin/manual-payment-requests/1/cancel', false)
            ->assertDontSee('value="bkash:1"', false)
            ->assertDontSee('id="pending-bulk-select-all"', false)
            ->assertDontSee('name="bulk_action"', false);
    }

    public function test_admin_manual_payment_approval_requires_valid_pin_and_credits_balance_once(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();
        $this->ensureManualPaymentRequestsTable();

        $admin = $this->createLoginUser(604, [
            'name' => 'Manual Approve Admin',
            'email' => 'manual-approve-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(605, [
            'name' => 'Manual Approve User',
            'email' => 'manual-approve-user@example.com',
            'main_bal' => 50,
        ]);

        DB::table('manual_payment_requests')->insert([
            'id' => 1,
            'user_id' => $user->id,
            'method' => 'Nagad',
            'sender_number' => '01912345678',
            'transaction_id' => 'NAGAD-APP-001',
            'amount' => 150,
            'note' => 'Approval test',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/admin/manual-payment-requests/1/approve')
            ->post('/admin/manual-payment-requests/1/confirm', [
                'admin_note' => 'Approved by admin',
                'pin' => '9999',
            ])
            ->assertRedirect('/admin/manual-payment-requests/1/approve')
            ->assertSessionHas('error', 'Invalid PIN!');

        $this->assertDatabaseHas('manual_payment_requests', [
            'id' => 1,
            'status' => 'pending',
        ]);
        $this->assertSame(50.0, (float) $user->fresh()->main_bal);

        $this->actingAs($admin)
            ->post('/admin/manual-payment-requests/1/confirm', [
                'admin_note' => 'Approved by admin',
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Manual payment request approved successfully!');

        $this->assertDatabaseHas('manual_payment_requests', [
            'id' => 1,
            'status' => 'approved',
            'admin_note' => 'Approved by admin',
        ]);
        $this->assertSame(200.0, (float) $user->fresh()->main_bal);
        $this->assertDatabaseHas('balance_add_history', [
            'user_id' => $user->id,
            'amount' => 150,
            'type' => 'nagad',
            'description' => 'Approved by admin',
        ]);
    }

    public function test_admin_can_fail_and_cancel_manual_payment_requests_without_crediting_balance(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();
        $this->ensureManualPaymentRequestsTable();

        $admin = $this->createLoginUser(606, [
            'name' => 'Manual Reject Admin',
            'email' => 'manual-reject-admin@example.com',
            'is_admin' => true,
        ]);

        $failedUser = $this->createLoginUser(607, [
            'name' => 'Manual Failed User',
            'email' => 'manual-failed-user@example.com',
            'main_bal' => 70,
        ]);

        $cancelUser = $this->createLoginUser(608, [
            'name' => 'Manual Cancel User',
            'email' => 'manual-cancel-user@example.com',
            'main_bal' => 95,
        ]);

        DB::table('manual_payment_requests')->insert([
            [
                'id' => 1,
                'user_id' => $failedUser->id,
                'method' => 'Rocket',
                'sender_number' => '01812345678',
                'transaction_id' => 'ROCKET-FAIL-001',
                'amount' => 120,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => $cancelUser->id,
                'method' => 'Upay',
                'sender_number' => '01312345678',
                'transaction_id' => 'UPAY-CANCEL-001',
                'amount' => 200,
                'status' => 'pending',
                'created_at' => now()->addSecond(),
                'updated_at' => now()->addSecond(),
            ],
        ]);

        $this->actingAs($admin)
            ->post('/admin/manual-payment-requests/1/confirm-failed', [
                'admin_note' => 'Invalid transaction',
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Manual payment request marked as failed successfully!');

        $this->assertDatabaseHas('manual_payment_requests', [
            'id' => 1,
            'status' => 'rejected',
            'admin_note' => 'Invalid transaction',
        ]);
        $this->assertSame(70.0, (float) $failedUser->fresh()->main_bal);

        $this->actingAs($admin)
            ->post('/admin/manual-payment-requests/2/confirm-cancel', [
                'admin_note' => 'Cancelled by admin',
                'pin' => '1234',
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Manual payment request cancelled successfully!');

        $this->assertDatabaseHas('manual_payment_requests', [
            'id' => 2,
            'status' => 'rejected',
            'admin_note' => 'Cancelled by admin',
        ]);
        $this->assertSame(95.0, (float) $cancelUser->fresh()->main_bal);
        $this->assertDatabaseCount('balance_add_history', 0);
    }

    public function test_dashboard_add_balance_card_points_to_user_add_balance_route(): void
    {
        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('balance_add_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $user = new User();
        $user->forceFill([
            'id' => 104,
            'name' => 'Dashboard User',
            'main_bal' => 1500,
            'drive_bal' => 500,
            'bank_bal' => 200,
            'is_admin' => 0,
            'permissions' => json_encode(['add_balance']),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('user.add.balance'), false);
    }

    public function test_dashboard_flexi_links_point_to_user_flexi_route(): void
    {
        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('balance_add_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $user = new User();
        $user->forceFill([
            'id' => 105,
            'name' => 'Flexi Dashboard User',
            'main_bal' => 1500,
            'drive_bal' => 500,
            'bank_bal' => 200,
            'is_admin' => 0,
            'permissions' => json_encode([]),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('user.flexi'), false);
    }

    public function test_dashboard_shows_provider_api_docs_section(): void
    {
        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('balance_add_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $user = new User();
        $user->forceFill([
            'id' => 106,
            'name' => 'API Docs Dashboard User',
            'main_bal' => 1500,
            'drive_bal' => 500,
            'bank_bal' => 200,
            'is_admin' => 0,
            'permissions' => json_encode([]),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Simple Provider API Documentation')
            ->assertSee('POST /api/v1/auth-check')
            ->assertSee('X-Client-Domain: yourdomain.com');
    }

    public function test_dashboard_total_usage_section_uses_real_history_data(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drive_history')) {
            Schema::create('drive_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('success');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('recharge_history')) {
            Schema::create('recharge_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('type')->nullable();
                $table->timestamps();
            });
        }

        $this->travelTo(\Carbon\Carbon::parse('2026-03-07 10:00:00'));

        try {
            $user = $this->createLoginUser(340, [
                'name' => 'Usage Dashboard User',
                'email' => 'usage-dashboard-user@example.com',
                'permissions' => ['add_balance'],
            ]);

            DB::table('drive_history')->insert([
                [
                    'user_id' => $user->id,
                    'operator' => 'Robi',
                    'mobile' => '01800000000',
                    'amount' => 100,
                    'status' => 'success',
                    'description' => 'Drive success',
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3),
                ],
                [
                    'user_id' => $user->id,
                    'operator' => 'Teletalk',
                    'mobile' => '01500000000',
                    'amount' => 999,
                    'status' => 'failed',
                    'description' => 'Drive failed',
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ],
            ]);

            DB::table('regular_requests')->insert([
                [
                    'user_id' => $user->id,
                    'operator' => 'Banglalink',
                    'mobile' => '01900000000',
                    'amount' => 150,
                    'status' => 'approved',
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ],
                [
                    'user_id' => $user->id,
                    'operator' => 'Airtel',
                    'mobile' => '01600000000',
                    'amount' => 300,
                    'status' => 'rejected',
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ],
            ]);

            DB::table('recharge_history')->insert([
                [
                    'user_id' => $user->id,
                    'amount' => 75,
                    'type' => 'Bkash',
                    'created_at' => now()->subDays(4),
                    'updated_at' => now()->subDays(4),
                ],
                [
                    'user_id' => $user->id,
                    'amount' => 200,
                    'type' => 'Internet Pack Combo',
                    'created_at' => now()->subHours(2),
                    'updated_at' => now()->subHours(2),
                ],
            ]);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('Total Usage')
                ->assertSee('৳ 325.00')
                ->assertSee('3')
                ->assertSee('03 Mar 2026 - 06 Mar 2026')
                ->assertSee('Successful requests and recharges')
                ->assertSee('1 day ago')
                ->assertSee('on Banglalink')
                ->assertDontSee('৳ 1,624.00')
                ->assertDontSee('on Internet Pack Combo');
        } finally {
            $this->travelBack();
        }
    }

    public function test_admin_add_balance_page_shows_pin_description_and_return_balance_action(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();

        $admin = $this->createLoginUser(320, [
            'name' => 'Admin Balance Manager',
            'email' => 'admin-balance-manager@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(321, [
            'name' => 'Balance Target User',
            'email' => 'balance-target-user@example.com',
            'main_bal' => 100,
            'drive_bal' => 50,
            'bank_bal' => 25,
        ]);

        $this->actingAs($admin)
            ->get('/admin/add-balance/' . $user->id)
            ->assertOk()
            ->assertSee('name="pin"', false)
            ->assertSee('name="description"', false)
            ->assertSee(route('admin.return.balance', $user->id), false)
            ->assertSee(route('admin.resellers'), false);
    }

    public function test_admin_add_balance_requires_valid_pin_and_redirects_to_reseller_all_tab_after_success(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();

        $admin = $this->createLoginUser(322, [
            'name' => 'Add Balance Admin',
            'email' => 'add-balance-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(323, [
            'name' => 'Add Balance User',
            'email' => 'add-balance-user@example.com',
            'main_bal' => 100,
            'drive_bal' => 20,
            'bank_bal' => 10,
        ]);

        $this->actingAs($admin)
            ->from('/admin/add-balance/' . $user->id)
            ->post('/admin/add-balance/' . $user->id, [
                'balance_type' => 'main_bal',
                'amount' => '50',
                'pin' => '9999',
                'description' => 'Invalid pin attempt',
            ])
            ->assertRedirect('/admin/add-balance/' . $user->id)
            ->assertSessionHasErrors(['pin' => 'Invalid admin PIN.']);

        $this->assertSame(100.0, (float) DB::table('users')->where('id', $user->id)->value('main_bal'));
        $this->assertSame(0, DB::table('balance_add_history')->count());

        $this->actingAs($admin)
            ->post('/admin/add-balance/' . $user->id, [
                'balance_type' => 'main_bal',
                'amount' => '50',
                'pin' => '1234',
                'description' => 'Manual add by admin',
            ])
            ->assertRedirect('/admin/resellers')
            ->assertSessionHas('success', 'Balance added successfully!');

        $this->assertSame(150.0, (float) DB::table('users')->where('id', $user->id)->value('main_bal'));
        $this->assertDatabaseHas('balance_add_history', [
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'Main Balance',
            'description' => 'Manual add by admin',
        ]);
    }

    public function test_admin_return_balance_requires_valid_pin_and_stores_description(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();

        $admin = $this->createLoginUser(324, [
            'name' => 'Return Balance Admin',
            'email' => 'return-balance-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(325, [
            'name' => 'Return Balance User',
            'email' => 'return-balance-user@example.com',
            'main_bal' => 120,
            'drive_bal' => 40,
            'bank_bal' => 15,
        ]);

        $this->actingAs($admin)
            ->from('/admin/return-balance/' . $user->id)
            ->post('/admin/return-balance/' . $user->id, [
                'balance_type' => 'main_bal',
                'amount' => '20',
                'pin' => '9999',
                'description' => 'Wrong pin return attempt',
            ])
            ->assertRedirect('/admin/return-balance/' . $user->id)
            ->assertSessionHasErrors(['pin' => 'Invalid admin PIN.']);

        $this->assertSame(120.0, (float) DB::table('users')->where('id', $user->id)->value('main_bal'));
        $this->assertSame(0, DB::table('balance_add_history')->count());

        $this->actingAs($admin)
            ->post('/admin/return-balance/' . $user->id, [
                'balance_type' => 'main_bal',
                'amount' => '20',
                'pin' => '1234',
                'description' => 'Returned for adjustment',
            ])
            ->assertRedirect('/admin/resellers')
            ->assertSessionHas('success', 'Balance returned successfully!');

        $this->assertSame(100.0, (float) DB::table('users')->where('id', $user->id)->value('main_bal'));
        $this->assertDatabaseHas('balance_add_history', [
            'user_id' => $user->id,
            'amount' => 20,
            'type' => 'Returned: Main Balance',
            'description' => 'Returned for adjustment',
        ]);
    }

    public function test_admin_can_add_regular_package_from_manage_package_page(): void
    {
        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission', 10, 2);
            $table->date('expire');
            $table->string('status')->default('active');
            $table->integer('sell_today')->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('comm', 10, 2)->default(0);
            $table->timestamps();
        });

        $admin = new User();
        $admin->forceFill([
            'id' => 105,
            'name' => 'Regular Package Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/admin/manage-regular-package/Grameenphone')
            ->assertOk()
            ->assertSee('name="package_name"', false)
            ->assertSee('value="active"', false)
            ->assertSee('value="deactive"', false);

        $this->actingAs($admin)
            ->post('/admin/manage-regular-package/Grameenphone/store', [
                'package_name' => '50GB + 1000 min',
                'price' => '499',
                'commission' => '20',
                'expire' => '2026-12-31',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/manage-regular-package/Grameenphone');

        $this->assertSame('50GB + 1000 min', DB::table('regular_packages')->where('operator', 'Grameenphone')->value('name'));
        $this->assertSame('active', DB::table('regular_packages')->where('operator', 'Grameenphone')->value('status'));
    }

    public function test_admin_resellers_page_shows_bulk_action_controls(): void
    {
        $admin = $this->createLoginUser(326, [
            'name' => 'Reseller Admin',
            'email' => 'reseller-admin@example.com',
            'is_admin' => true,
        ]);

        $listedUser = $this->createLoginUser(327, [
            'name' => 'Bulk Listed User',
            'email' => 'bulk-listed-user@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/resellers')
            ->assertOk()
            ->assertSee('Bulk Action')
            ->assertSee('--Select--')
            ->assertSee('Cancel OTP')
            ->assertSee(route('admin.resellers.bulk-action'), false)
            ->assertSee(route('admin.resellers.show', $listedUser), false)
            ->assertSee('Bulk Listed User')
            ->assertSee('bKash')
            ->assertSee('Nagad')
            ->assertSee('Rocket')
            ->assertSee('Upay')
            ->assertSee('Islami Bank')
            ->assertDontSee('<a href="' . route('admin.deleted.accounts') . '" class="btn btn-warning">Deleted Accounts</a>', false);
    }

    public function test_admin_resellers_page_shows_google_otp_on_off_statuses(): void
    {
        $admin = $this->createLoginUser(328, [
            'name' => 'OTP Status Admin',
            'email' => 'otp-status-admin@example.com',
            'is_admin' => true,
        ]);

        $this->createLoginUser(329, [
            'name' => 'OTP Enabled Reseller',
            'email' => 'otp-enabled-reseller@example.com',
            'google_otp_secret' => 'JBSWY3DPEHPK3PXP',
            'google_otp_enabled' => true,
            'google_otp_confirmed_at' => now(),
        ]);

        $this->createLoginUser(330, [
            'name' => 'OTP Disabled Reseller',
            'email' => 'otp-disabled-reseller@example.com',
            'google_otp_enabled' => false,
        ]);

        $this->actingAs($admin)
            ->get('/admin/resellers')
            ->assertOk()
            ->assertSee('OTP Enabled Reseller')
            ->assertSee('OTP Disabled Reseller')
            ->assertSee('<span class="badge badge-success">On</span>', false)
            ->assertSee('<span class="badge badge-error">Off</span>', false);
    }

    public function test_admin_can_bulk_activate_deactivate_and_cancel_reseller_otp(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('otp', 6);
            $table->string('type');
            $table->string('channel')->default('email');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();
        });

        $admin = $this->createLoginUser(328, [
            'name' => 'Bulk Action Admin',
            'email' => 'bulk-action-admin@example.com',
            'is_admin' => true,
        ]);

        $firstUser = $this->createLoginUser(329, [
            'name' => 'First Bulk User',
            'email' => 'first-bulk-user@example.com',
            'is_active' => false,
        ]);

        $secondUser = $this->createLoginUser(330, [
            'name' => 'Second Bulk User',
            'email' => 'second-bulk-user@example.com',
            'is_active' => false,
        ]);

        DB::table('otps')->insert([
            [
                'email' => $firstUser->email,
                'otp' => '123456',
                'type' => 'registration',
                'channel' => 'email',
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => $secondUser->email,
                'otp' => '654321',
                'type' => 'registration',
                'channel' => 'email',
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->from('/admin/resellers')
            ->post(route('admin.resellers.bulk-action'), [
                'action' => 'active',
                'user_ids' => [$firstUser->id, $secondUser->id],
            ])
            ->assertRedirect('/admin/resellers')
            ->assertSessionHas('success', 'Selected reseller accounts activated successfully.');

        $this->assertTrue((bool) DB::table('users')->where('id', $firstUser->id)->value('is_active'));
        $this->assertTrue((bool) DB::table('users')->where('id', $secondUser->id)->value('is_active'));

        $this->actingAs($admin)
            ->from('/admin/resellers')
            ->post(route('admin.resellers.bulk-action'), [
                'action' => 'deactive',
                'user_ids' => [$firstUser->id, $secondUser->id],
            ])
            ->assertRedirect('/admin/resellers')
            ->assertSessionHas('success', 'Selected reseller accounts deactivated successfully.');

        $this->assertFalse((bool) DB::table('users')->where('id', $firstUser->id)->value('is_active'));
        $this->assertFalse((bool) DB::table('users')->where('id', $secondUser->id)->value('is_active'));

        $this->actingAs($admin)
            ->from('/admin/resellers')
            ->post(route('admin.resellers.bulk-action'), [
                'action' => 'cancel_otp',
                'user_ids' => [$firstUser->id, $secondUser->id],
            ])
            ->assertRedirect('/admin/resellers')
            ->assertSessionHas('success', 'Selected reseller OTPs cancelled successfully.');

        $this->assertSame(0, DB::table('otps')->count());
    }

    public function test_admin_can_bulk_delete_resellers_and_restore_them_from_deleted_accounts(): void
    {
        $admin = $this->createLoginUser(331, [
            'name' => 'Delete Restore Admin',
            'email' => 'delete-restore-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(332, [
            'name' => 'Deleted Reseller User',
            'email' => 'deleted-reseller-user@example.com',
        ]);

        $this->actingAs($admin)
            ->from('/admin/resellers')
            ->post(route('admin.resellers.bulk-action'), [
                'action' => 'delete',
                'user_ids' => [$user->id],
            ])
            ->assertRedirect('/admin/resellers')
            ->assertSessionHas('success', 'Selected reseller accounts deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->actingAs($admin)
            ->get(route('admin.deleted.accounts'))
            ->assertOk()
            ->assertSee('Deleted Accounts')
            ->assertSee('deleted-reseller-user@example.com');

        $this->actingAs($admin)
            ->post(route('admin.deleted.accounts.restore', $user->id))
            ->assertRedirect(route('admin.deleted.accounts'))
            ->assertSessionHas('success', 'Reseller account restored successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_reseller_detail_page_requires_manage_resellers_permission(): void
    {
        $admin = $this->createLoginUser(334, [
            'name' => 'Restricted Admin',
            'email' => 'restricted-admin@example.com',
            'is_admin' => true,
            'permissions' => [],
        ]);

        $user = $this->createLoginUser(335, [
            'name' => 'Target Reseller',
            'email' => 'target-reseller@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.resellers.show', $user))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access this page.');
    }

    public function test_first_admin_can_access_reseller_detail_without_explicit_manage_resellers_permission(): void
    {
        $admin = $this->createLoginUser(336, [
            'name' => 'First Admin',
            'email' => 'first-admin@example.com',
            'is_admin' => true,
            'is_first_admin' => true,
            'permissions' => [],
        ]);

        $user = $this->createLoginUser(337, [
            'name' => 'Visible Reseller',
            'email' => 'visible-reseller@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.resellers.show', $user))
            ->assertOk()
            ->assertSee('Reseller Details')
            ->assertSee('Admin PIN')
            ->assertSee('visible-reseller@example.com')
            ->assertSee('bKash')
            ->assertSee('Nagad')
            ->assertSee('Rocket')
            ->assertSee('Upay')
            ->assertSee('Islami Bank')
            ->assertDontSee('Back to Resellers');
    }

    public function test_admin_can_create_and_update_reseller_permissions(): void
    {
        $admin = $this->createLoginUser(338, [
            'name' => 'Permission Admin',
            'email' => 'permission-admin@example.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Created Reseller',
                'email' => 'created-reseller@example.com',
                'password' => 'secret123',
                'pin' => '1234',
                'level' => 'dealer',
                'permissions' => ['add_balance', 'bkash', 'complaints'],
            ])
            ->assertRedirect(route('admin.resellers'));

        $createdUser = User::query()->where('email', 'created-reseller@example.com')->firstOrFail();

        $this->assertSame(['add_balance', 'bkash', 'complaints'], $createdUser->permissionKeys());

        $this->actingAs($admin)
            ->put(route('admin.resellers.update', $createdUser), [
                'name' => 'Updated Reseller',
                'email' => 'created-reseller@example.com',
                'password' => '',
                'pin' => '',
                'admin_pin' => '1234',
                'level' => 'seller',
                'permissions' => ['profile', 'drive', 'rocket', 'islami_bank'],
            ])
            ->assertRedirect(route('admin.resellers.show', $createdUser));

        $createdUser->refresh();

        $this->assertSame('Updated Reseller', $createdUser->name);
        $this->assertSame('seller', $createdUser->level);
        $this->assertSame(['profile', 'drive', 'rocket', 'islami_bank'], $createdUser->permissionKeys());
    }

    public function test_admin_cannot_update_reseller_with_invalid_admin_pin(): void
    {
        $admin = $this->createLoginUser(339, [
            'name' => 'Reseller Update Admin',
            'email' => 'reseller-update-admin@example.com',
            'is_admin' => true,
        ]);

        $user = $this->createLoginUser(340, [
            'name' => 'Protected Reseller',
            'email' => 'protected-reseller@example.com',
            'level' => 'dealer',
            'permissions' => ['add_balance'],
        ]);

        $this->actingAs($admin)
            ->from(route('admin.resellers.show', $user))
            ->put(route('admin.resellers.update', $user), [
                'name' => 'Tampered Reseller',
                'email' => 'protected-reseller@example.com',
                'password' => '',
                'pin' => '',
                'admin_pin' => '9999',
                'level' => 'seller',
                'permissions' => ['drive', 'rocket'],
            ])
            ->assertRedirect(route('admin.resellers.show', $user))
            ->assertSessionHasErrors(['admin_pin' => 'Invalid admin PIN.']);

        $user->refresh();

        $this->assertSame('Protected Reseller', $user->name);
        $this->assertSame('dealer', $user->level);
        $this->assertSame(['add_balance'], $user->permissionKeys());
    }

    public function test_dashboard_hides_links_that_user_does_not_have_permission_for(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        $user = $this->createLoginUser(339, [
            'name' => 'Limited Dashboard User',
            'email' => 'limited-dashboard-user@example.com',
            'permissions' => ['drive'],
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('user.drive'), false)
            ->assertDontSee(route('user.add.balance'), false)
            ->assertDontSee(route('user.internet'), false)
            ->assertDontSee(route('user.profile'), false)
            ->assertDontSee(route('user.profile.google-otp'), false)
            ->assertDontSee(route('user.profile.api'), false)
            ->assertDontSee(route('complaints.index'), false)
            ->assertDontSee(route('user.all.history'), false);
    }

    public function test_complaints_routes_require_permission(): void
    {
        $user = $this->createLoginUser(340, [
            'name' => 'No Complaint Permission User',
            'email' => 'no-complaint-permission-user@example.com',
            'permissions' => [],
        ]);

        $this->actingAs($user)
            ->get(route('complaints.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access this page.');

        auth()->logout();

        $this->get(route('complaints.index'))
            ->assertRedirect(route('login'));
    }

    public function test_deleted_reseller_cannot_login(): void
    {
        $user = $this->createLoginUser(333, [
            'name' => 'Soft Deleted User',
            'email' => 'soft-deleted-user@example.com',
        ]);

        $user->delete();

        $response = $this->from('/login')->post('/login', [
            'email' => 'soft-deleted-user@example.com',
            'password' => 'secret123',
            'pin' => '1234',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
        $this->assertGuest();
    }

    public function test_admin_can_add_drive_package_from_manage_package_page(): void
    {
        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission', 10, 2);
            $table->date('expire');
            $table->string('status')->default('active');
            $table->integer('sell_today')->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('comm', 10, 2)->default(0);
            $table->timestamps();
        });

        $admin = new User();
        $admin->forceFill([
            'id' => 106,
            'name' => 'Drive Package Admin',
            'is_admin' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/admin/manage-drive-package/Grameenphone')
            ->assertOk()
            ->assertSee('name="package_name"', false)
            ->assertSee('value="active"', false)
            ->assertSee('value="deactive"', false);

        $this->actingAs($admin)
            ->post('/admin/manage-drive-package/Grameenphone/store', [
                'package_name' => '20GB Drive Offer',
                'price' => '299',
                'commission' => '15',
                'expire' => '2026-12-31',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/manage-drive-package/Grameenphone');

        $this->assertSame('20GB Drive Offer', DB::table('drive_packages')->where('operator', 'Grameenphone')->value('name'));
        $this->assertSame('active', DB::table('drive_packages')->where('operator', 'Grameenphone')->value('status'));
    }

    public function test_drive_purchase_rejects_invalid_user_pin(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 201,
            'name' => 'Drive Buyer',
            'email' => 'drivebuyer@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['drive']),
            'drive_bal' => 500,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => '20GB Drive Offer',
            'price' => 300,
            'commission' => 20,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(201);

        $this->actingAs($user)
            ->postJson('/drive-offers/Grameenphone/buy/' . $packageId, [
                'mobile' => '01712345678',
                'pin' => '9999',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid PIN',
            ]);

        $this->assertSame(0, DB::table('drive_requests')->count());
        $this->assertSame(500.0, (float) DB::table('users')->where('id', 201)->value('drive_bal'));
    }

    public function test_drive_purchase_accepts_correct_user_pin(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('balance_type')->nullable();
                $table->timestamps();
            });
        }

        DB::table('brandings')->insert([
            'id' => 1,
            'drive_balance' => 'on',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 202,
            'name' => 'Drive Buyer Success',
            'email' => 'drivebuyer-success@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['drive']),
            'main_bal' => 800,
            'drive_bal' => 500,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => '30GB Drive Offer',
            'price' => 350,
            'commission' => 50,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(202);

        $this->actingAs($user)
            ->postJson('/drive-offers/Grameenphone/buy/' . $packageId, [
                'mobile' => '01712345678',
                'pin' => '1234',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSame(1, DB::table('drive_requests')->where('user_id', 202)->count());
        $this->assertSame('pending', DB::table('drive_requests')->where('user_id', 202)->value('status'));
        $this->assertSame('drive_bal', DB::table('drive_requests')->where('user_id', 202)->value('balance_type'));
        $this->assertSame(200.0, (float) DB::table('users')->where('id', 202)->value('drive_bal'));
        $this->assertSame(800.0, (float) DB::table('users')->where('id', 202)->value('main_bal'));
    }

    public function test_drive_purchase_rejects_when_drive_balance_is_insufficient(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('balance_type')->nullable();
                $table->timestamps();
            });
        }

        DB::table('brandings')->insert([
            'id' => 1,
            'drive_balance' => 'on',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 206,
            'name' => 'Low Drive Balance User',
            'email' => 'low-drive@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['drive']),
            'main_bal' => 500,
            'drive_bal' => 50,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Large Drive Pack',
            'price' => 120,
            'commission' => 10,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(206);

        $this->actingAs($user)
            ->postJson('/drive-offers/Grameenphone/buy/' . $packageId, [
                'mobile' => '01712345678',
                'pin' => '1234',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient drive balance',
            ]);

        $this->assertSame(0, DB::table('drive_requests')->where('user_id', 206)->count());
        $this->assertSame(50.0, (float) DB::table('users')->where('id', 206)->value('drive_bal'));
        $this->assertSame(500.0, (float) DB::table('users')->where('id', 206)->value('main_bal'));
    }

    public function test_drive_purchase_uses_main_balance_when_drive_balance_setting_is_off(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('balance_type')->nullable();
                $table->timestamps();
            });
        }

        DB::table('brandings')->insert([
            'id' => 1,
            'drive_balance' => 'off',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 207,
            'name' => 'Main Balance Drive Buyer',
            'email' => 'main-balance-drive-buyer@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['drive']),
            'main_bal' => 500,
            'drive_bal' => 90,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Main Balance Drive Offer',
            'price' => 150,
            'commission' => 20,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(207);

        $this->actingAs($user)
            ->postJson('/drive-offers/Grameenphone/buy/' . $packageId, [
                'mobile' => '01712345678',
                'pin' => '1234',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSame('main_bal', DB::table('drive_requests')->where('user_id', 207)->value('balance_type'));
        $this->assertSame(370.0, (float) DB::table('users')->where('id', 207)->value('main_bal'));
        $this->assertSame(90.0, (float) DB::table('users')->where('id', 207)->value('drive_bal'));
    }

    public function test_drive_purchase_rejects_when_main_balance_is_insufficient_if_drive_balance_setting_is_off(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('balance_type')->nullable();
                $table->timestamps();
            });
        }

        DB::table('brandings')->insert([
            'id' => 1,
            'drive_balance' => 'off',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 208,
            'name' => 'Low Main Balance Drive Buyer',
            'email' => 'low-main-balance-drive-buyer@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['drive']),
            'main_bal' => 60,
            'drive_bal' => 500,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Main Balance Required Offer',
            'price' => 120,
            'commission' => 10,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(208);

        $this->actingAs($user)
            ->postJson('/drive-offers/Grameenphone/buy/' . $packageId, [
                'mobile' => '01712345678',
                'pin' => '1234',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient main balance',
            ]);

        $this->assertSame(0, DB::table('drive_requests')->where('user_id', 208)->count());
        $this->assertSame(60.0, (float) DB::table('users')->where('id', 208)->value('main_bal'));
        $this->assertSame(500.0, (float) DB::table('users')->where('id', 208)->value('drive_bal'));
    }

    public function test_admin_can_cancel_drive_request_without_invalid_status_value(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id');
                $table->string('operator');
                $table->string('mobile');
                $table->decimal('amount', 10, 2)->default(0);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->string('balance_type')->nullable();
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 211,
            'name' => 'Drive Cancel Admin',
            'email' => 'drive-cancel-admin@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'drive_bal' => 0,
            'is_admin' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 212,
            'name' => 'Drive Cancel User',
            'email' => 'drive-cancel-user@example.com',
            'password' => 'secret',
            'pin' => Hash::make('5678'),
            'main_bal' => 20,
            'drive_bal' => 10,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Cancelable Drive Offer',
            'price' => 100,
            'commission' => 10,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $requestId = DB::table('drive_requests')->insertGetId([
            'user_id' => 212,
            'package_id' => $packageId,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 90,
            'status' => 'pending',
            'balance_type' => 'drive_bal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::query()->findOrFail(211);

        $this->actingAs($admin)
            ->post('/admin/drive-requests/' . $requestId . '/confirm-cancel', [
                'pin' => '1234',
                'description' => 'Cancelled by admin',
            ])
            ->assertStatus(302);

        $this->assertSame('rejected', DB::table('drive_requests')->where('id', $requestId)->value('status'));
        $this->assertSame(100.0, (float) DB::table('users')->where('id', 212)->value('drive_bal'));
        $this->assertSame(20.0, (float) DB::table('users')->where('id', 212)->value('main_bal'));
    }

    public function test_admin_cancel_drive_request_refunds_main_balance_when_purchase_used_main_balance(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id');
                $table->string('operator');
                $table->string('mobile');
                $table->decimal('amount', 10, 2)->default(0);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->string('balance_type')->nullable();
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 209,
            'name' => 'Main Balance Refund Admin',
            'email' => 'main-balance-refund-admin@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'main_bal' => 0,
            'drive_bal' => 0,
            'is_admin' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 210,
            'name' => 'Main Balance Refund User',
            'email' => 'main-balance-refund-user@example.com',
            'password' => 'secret',
            'pin' => Hash::make('5678'),
            'main_bal' => 40,
            'drive_bal' => 15,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Refundable Main Balance Offer',
            'price' => 100,
            'commission' => 10,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $requestId = DB::table('drive_requests')->insertGetId([
            'user_id' => 210,
            'package_id' => $packageId,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 90,
            'status' => 'pending',
            'balance_type' => 'main_bal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::query()->findOrFail(209);

        $this->actingAs($admin)
            ->post('/admin/drive-requests/' . $requestId . '/confirm-cancel', [
                'pin' => '1234',
                'description' => 'Cancelled by admin',
            ])
            ->assertStatus(302);

        $this->assertSame('rejected', DB::table('drive_requests')->where('id', $requestId)->value('status'));
        $this->assertSame(130.0, (float) DB::table('users')->where('id', 210)->value('main_bal'));
        $this->assertSame(15.0, (float) DB::table('users')->where('id', 210)->value('drive_bal'));
    }

    public function test_admin_branding_update_can_store_drive_balance_setting(): void
    {
        $admin = $this->createLoginUser(230, [
            'name' => 'Branding Admin',
            'email' => 'branding-admin@example.com',
            'is_admin' => true,
        ]);

        DB::table('brandings')->insert([
            'id' => 1,
            'brand_name' => 'Codecartel',
            'drive_balance' => 'on',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/branding/update', [
                'brand_name' => 'Codecartel Telecom',
                'drive_balance' => 'off',
            ])
            ->assertRedirect();

        $this->assertSame('off', DB::table('brandings')->where('id', 1)->value('drive_balance'));
    }

    public function test_admin_branding_page_marks_saved_drive_balance_option_as_checked(): void
    {
        $admin = $this->createLoginUser(231, [
            'name' => 'Branding Viewer',
            'email' => 'branding-viewer@example.com',
            'is_admin' => true,
        ]);

        DB::table('brandings')->insert([
            'id' => 1,
            'brand_name' => 'Codecartel',
            'drive_balance' => 'off',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/branding')
            ->assertOk()
            ->assertSee('id="drive_balance_off"', false)
            ->assertSee('name="drive_balance" value="off" checked', false)
            ->assertSee('id="drive_balance_on"', false);
    }

    public function test_admin_pending_requests_page_shows_type_column_after_operator(): void
    {
        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('drive_packages')->insert([
            'id' => 1,
            'operator' => 'Grameenphone',
            'name' => 'Pending Drive Package',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_packages')->insert([
            'id' => 1,
            'operator' => 'Robi',
            'name' => 'Pending Internet Package',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createLoginUser(213, [
            'name' => 'Pending Admin',
            'email' => 'pending-admin@example.com',
            'is_admin' => true,
        ]);

        $requestUser = $this->createLoginUser(214, [
            'name' => 'Pending Request User',
            'email' => 'pending-request-user@example.com',
        ]);

        $olderRequestUser = $this->createLoginUser(215, [
            'name' => 'Older Pending User',
            'email' => 'older-pending-user@example.com',
        ]);

        DB::table('drive_requests')->insert([
            'id' => 1,
            'user_id' => $requestUser->id,
            'package_id' => 1,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 99,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            'id' => 2,
            'user_id' => $olderRequestUser->id,
            'package_id' => 1,
            'operator' => 'Robi',
            'mobile' => '01812345678',
            'amount' => 49,
            'status' => 'pending',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/pending-drive-requests');

        $response->assertOk();
        $this->assertMatchesRegularExpression('/<th>[\s\S]*?Select[\s\S]*?pending-bulk-select-all[\s\S]*?<\/th>\s*<th>Sl<\/th>\s*<th>User<\/th>\s*<th>Operator<\/th>\s*<th>Type<\/th>\s*<th>Package<\/th>/s', $response->getContent());
        $this->assertMatchesRegularExpression('/name="request_keys\[\]"[\s\S]*?<\/td>\s*<td>2<\/td>\s*<td>Pending Request User<\/td>\s*<td><span class="badge badge-primary">Grameenphone<\/span><\/td>\s*<td><span class="badge badge-info">Drive<\/span><\/td>/s', $response->getContent());
        $this->assertMatchesRegularExpression('/name="request_keys\[\]"[\s\S]*?<\/td>\s*<td>1<\/td>\s*<td>Older Pending User<\/td>\s*<td><span class="badge badge-primary">Robi<\/span><\/td>\s*<td><span class="badge badge-info">Internet<\/span><\/td>/s', $response->getContent());
    }

    public function test_admin_pending_requests_page_shows_filters_print_and_bulk_action_controls(): void
    {
        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('drive_packages')->insert([
            'id' => 11,
            'operator' => 'Grameenphone',
            'name' => 'Filter Drive Package',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_packages')->insert([
            'id' => 11,
            'operator' => 'Robi',
            'name' => 'Filter Internet Package',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createLoginUser(216, [
            'name' => 'Pending Filter Admin',
            'email' => 'pending-filter-admin@example.com',
            'is_admin' => true,
        ]);

        $requestUser = $this->createLoginUser(217, [
            'name' => 'Filter Match User',
            'email' => 'filter-match-user@example.com',
        ]);

        $olderRequestUser = $this->createLoginUser(218, [
            'name' => 'Filtered Out User',
            'email' => 'filtered-out-user@example.com',
        ]);

        DB::table('drive_requests')->insert([
            'id' => 11,
            'user_id' => $requestUser->id,
            'package_id' => 11,
            'operator' => 'Grameenphone',
            'mobile' => '01712345678',
            'amount' => 99,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            'id' => 12,
            'user_id' => $olderRequestUser->id,
            'package_id' => 11,
            'operator' => 'Robi',
            'mobile' => '01812345678',
            'amount' => 49,
            'status' => 'pending',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/pending-drive-requests?show=50&number=01712345678&reseller=Filter+Match+User&service=drive&status=pending&date_from=' . now()->toDateString() . '&date_to=' . now()->toDateString());

        $response->assertOk()
            ->assertSee('Show')
            ->assertSee('Number')
            ->assertSee('Reseller')
            ->assertSee('Services')
            ->assertSee('Flexi')
            ->assertSee('Status')
            ->assertSee('Date From')
            ->assertSee('Date To')
            ->assertSee('Filter')
            ->assertSee('Print')
            ->assertSee('Bulk Action')
            ->assertSee('--Select--')
            ->assertSee('Resend')
            ->assertSee('Waiting')
            ->assertSee('Manual Complete')
            ->assertSee('Process')
            ->assertSee('Cancel')
            ->assertSee('id="pending-bulk-select-all"', false)
            ->assertSee('PIN')
            ->assertSee('Filter Match User')
            ->assertDontSee('Filtered Out User');
    }

    public function test_admin_pending_bulk_action_requires_selectable_request_keys(): void
    {
        $admin = $this->createLoginUser(455, [
            'name' => 'Pending Bulk Validate Admin',
            'email' => 'pending-bulk-validate-admin@example.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->from('/admin/pending-drive-requests?service=flexi')
            ->post(route('admin.pending.requests.bulk-action'), [
                'bulk_action' => 'waiting',
                'bulk_note' => 'Need review',
                'pin' => '1234',
                'service' => 'flexi',
            ])
            ->assertRedirect('/admin/pending-drive-requests?service=flexi')
            ->assertSessionHas('error', 'Please select at least one Drive or Internet pending request.');
    }

    public function test_admin_can_bulk_update_pending_request_workflow_status(): void
    {
        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        DB::table('drive_packages')->insert(['id' => 21, 'operator' => 'GP', 'name' => 'Drive', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('regular_packages')->insert(['id' => 21, 'operator' => 'Robi', 'name' => 'Internet', 'created_at' => now(), 'updated_at' => now()]);

        $admin = $this->createLoginUser(240, [
            'name' => 'Bulk Pending Admin',
            'email' => 'bulk-pending-admin@example.com',
            'is_admin' => true,
        ]);

        $driveUser = $this->createLoginUser(241, ['name' => 'Drive Workflow User', 'email' => 'drive-workflow@example.com']);
        $regularUser = $this->createLoginUser(242, ['name' => 'Regular Workflow User', 'email' => 'regular-workflow@example.com']);

        DB::table('drive_requests')->insert([
            'id' => 21,
            'user_id' => $driveUser->id,
            'package_id' => 21,
            'operator' => 'Grameenphone',
            'mobile' => '01700000000',
            'amount' => 100,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            'id' => 22,
            'user_id' => $regularUser->id,
            'package_id' => 21,
            'operator' => 'Robi',
            'mobile' => '01800000000',
            'amount' => 50,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/admin/pending-drive-requests')
            ->post(route('admin.pending.requests.bulk-action'), [
                'bulk_action' => 'waiting',
                'bulk_note' => 'Need manual review',
                'pin' => '1234',
                'request_keys' => ['drive:21', 'internet:22'],
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Selected pending requests updated successfully.');

        $this->assertSame('waiting', DB::table('drive_requests')->where('id', 21)->value('admin_status'));
        $this->assertSame('Need manual review', DB::table('drive_requests')->where('id', 21)->value('admin_note'));
        $this->assertSame('waiting', DB::table('regular_requests')->where('id', 22)->value('admin_status'));
        $this->assertSame('Need manual review', DB::table('regular_requests')->where('id', 22)->value('admin_note'));
    }

    public function test_admin_pending_bulk_action_preserves_filters_and_old_input_after_invalid_pin(): void
    {
        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        DB::table('drive_packages')->insert(['id' => 51, 'operator' => 'GP', 'name' => 'Drive', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('regular_packages')->insert(['id' => 51, 'operator' => 'Robi', 'name' => 'Internet', 'created_at' => now(), 'updated_at' => now()]);

        $admin = $this->createLoginUser(249, [
            'name' => 'Bulk Invalid Pin Admin',
            'email' => 'bulk-invalid-pin-admin@example.com',
            'is_admin' => true,
        ]);

        $driveUser = $this->createLoginUser(250, ['name' => 'Filtered Drive User', 'email' => 'filtered-drive-user@example.com']);
        $regularUser = $this->createLoginUser(251, ['name' => 'Filtered Regular User', 'email' => 'filtered-regular-user@example.com']);

        DB::table('drive_requests')->insert([
            'id' => 51,
            'user_id' => $driveUser->id,
            'package_id' => 51,
            'operator' => 'Grameenphone',
            'mobile' => '01755555555',
            'amount' => 100,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            'id' => 52,
            'user_id' => $regularUser->id,
            'package_id' => 51,
            'operator' => 'Robi',
            'mobile' => '01855555555',
            'amount' => 50,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.pending.requests.bulk-action'), [
                'bulk_action' => 'process',
                'bulk_note' => 'Need processing',
                'pin' => '9999',
                'request_keys' => ['drive:51', 'internet:52'],
                'show' => '25',
                'number' => '01755555555',
                'reseller' => 'Filtered Drive User',
                'service' => 'drive',
                'status' => 'pending',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invalid PIN!');

        $redirectUrl = $response->headers->get('Location');

        $this->assertSame('/admin/pending-drive-requests', parse_url($redirectUrl, PHP_URL_PATH));

        parse_str((string) parse_url($redirectUrl, PHP_URL_QUERY), $redirectQuery);

        $this->assertSame([
            'show' => '25',
            'number' => '01755555555',
            'reseller' => 'Filtered Drive User',
            'service' => 'drive',
            'status' => 'pending',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ], $redirectQuery);

        $redirectTarget = parse_url($redirectUrl, PHP_URL_PATH) . '?' . parse_url($redirectUrl, PHP_URL_QUERY);

        $followUpResponse = $this->actingAs($admin)->get($redirectTarget);

        $followUpResponse->assertOk()
            ->assertSee('Filtered Drive User')
            ->assertDontSee('Filtered Regular User')
            ->assertSee('value="01755555555"', false)
            ->assertSee('value="Filtered Drive User"', false)
            ->assertSee('<option value="process" selected>Process</option>', false)
            ->assertSee('value="Need processing"', false);

        $this->assertMatchesRegularExpression('/value="drive:51"[^>]*checked/s', $followUpResponse->getContent());
    }

    public function test_admin_pending_bulk_action_success_redirect_keeps_active_filters(): void
    {
        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        DB::table('drive_packages')->insert(['id' => 61, 'operator' => 'GP', 'name' => 'Drive', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('regular_packages')->insert(['id' => 61, 'operator' => 'Robi', 'name' => 'Internet', 'created_at' => now(), 'updated_at' => now()]);

        $admin = $this->createLoginUser(252, [
            'name' => 'Bulk Filter Success Admin',
            'email' => 'bulk-filter-success-admin@example.com',
            'is_admin' => true,
        ]);

        $driveUser = $this->createLoginUser(253, ['name' => 'Waiting Drive User', 'email' => 'waiting-drive-user@example.com']);
        $regularUser = $this->createLoginUser(254, ['name' => 'Waiting Regular User', 'email' => 'waiting-regular-user@example.com']);

        DB::table('drive_requests')->insert([
            'id' => 61,
            'user_id' => $driveUser->id,
            'package_id' => 61,
            'operator' => 'Grameenphone',
            'mobile' => '01766666666',
            'amount' => 100,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            'id' => 62,
            'user_id' => $regularUser->id,
            'package_id' => 61,
            'operator' => 'Robi',
            'mobile' => '01866666666',
            'amount' => 50,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.pending.requests.bulk-action'), [
                'bulk_action' => 'waiting',
                'bulk_note' => 'Need review',
                'pin' => '1234',
                'request_keys' => ['drive:61', 'internet:62'],
                'show' => '25',
                'service' => 'drive',
                'status' => 'waiting',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Selected pending requests updated successfully.');

        $redirectUrl = $response->headers->get('Location');

        $this->assertSame('/admin/pending-drive-requests', parse_url($redirectUrl, PHP_URL_PATH));

        parse_str((string) parse_url($redirectUrl, PHP_URL_QUERY), $redirectQuery);

        $this->assertSame([
            'show' => '25',
            'service' => 'drive',
            'status' => 'waiting',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ], $redirectQuery);

        $redirectTarget = parse_url($redirectUrl, PHP_URL_PATH) . '?' . parse_url($redirectUrl, PHP_URL_QUERY);

        $followUpResponse = $this->actingAs($admin)->get($redirectTarget);

        $followUpResponse->assertOk()
            ->assertSee('Waiting Drive User')
            ->assertDontSee('Waiting Regular User')
            ->assertSee('<option value="drive" selected>Drive</option>', false)
            ->assertSee('<option value="waiting" selected>Waiting</option>', false);

        $this->assertSame('waiting', DB::table('drive_requests')->where('id', 61)->value('admin_status'));
        $this->assertSame('waiting', DB::table('regular_requests')->where('id', 62)->value('admin_status'));
    }

    public function test_admin_can_bulk_manual_complete_pending_requests(): void
    {
        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        DB::table('drive_packages')->insert(['id' => 31, 'operator' => 'GP', 'name' => 'Drive', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('regular_packages')->insert(['id' => 31, 'operator' => 'BL', 'name' => 'Internet', 'created_at' => now(), 'updated_at' => now()]);

        $admin = $this->createLoginUser(243, [
            'name' => 'Bulk Complete Admin',
            'email' => 'bulk-complete-admin@example.com',
            'is_admin' => true,
        ]);

        $driveUser = $this->createLoginUser(244, ['name' => 'Drive Complete User', 'email' => 'drive-complete@example.com']);
        $regularUser = $this->createLoginUser(245, ['name' => 'Regular Complete User', 'email' => 'regular-complete@example.com']);

        DB::table('drive_requests')->insert([
            'id' => 31,
            'user_id' => $driveUser->id,
            'package_id' => 31,
            'operator' => 'Grameenphone',
            'mobile' => '01711111111',
            'amount' => 120,
            'status' => 'pending',
            'admin_status' => 'process',
            'admin_note' => 'In queue',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            'id' => 32,
            'user_id' => $regularUser->id,
            'package_id' => 31,
            'operator' => 'Banglalink',
            'mobile' => '01911111111',
            'amount' => 80,
            'status' => 'pending',
            'admin_status' => 'waiting',
            'admin_note' => 'Queued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/admin/pending-drive-requests')
            ->post(route('admin.pending.requests.bulk-action'), [
                'bulk_action' => 'manual_complete',
                'bulk_note' => 'Bulk manual complete note',
                'pin' => '1234',
                'request_keys' => ['drive:31', 'internet:32'],
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Selected pending requests updated successfully.');

        $this->assertSame('approved', DB::table('drive_requests')->where('id', 31)->value('status'));
        $this->assertNull(DB::table('drive_requests')->where('id', 31)->value('admin_status'));
        $this->assertSame('approved', DB::table('regular_requests')->where('id', 32)->value('status'));
        $this->assertSame('Bulk manual complete note', DB::table('regular_requests')->where('id', 32)->value('description'));
        $this->assertNull(DB::table('regular_requests')->where('id', 32)->value('admin_status'));
        $this->assertDatabaseHas('drive_history', [
            'user_id' => $driveUser->id,
            'package_id' => 31,
            'status' => 'success',
            'description' => 'Bulk manual complete note',
        ]);
    }

    public function test_admin_can_bulk_cancel_pending_requests_and_refund_balances(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('balance_type')->nullable();
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->string('admin_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        $admin = $this->createLoginUser(246, [
            'name' => 'Bulk Cancel Admin',
            'email' => 'bulk-cancel-admin@example.com',
            'is_admin' => true,
            'main_bal' => 0,
            'drive_bal' => 0,
        ]);

        $driveUser = $this->createLoginUser(247, [
            'name' => 'Drive Cancel Bulk User',
            'email' => 'drive-cancel-bulk@example.com',
            'main_bal' => 15,
            'drive_bal' => 5,
        ]);

        $regularUser = $this->createLoginUser(248, [
            'name' => 'Regular Cancel Bulk User',
            'email' => 'regular-cancel-bulk@example.com',
            'main_bal' => 20,
            'drive_bal' => 1,
        ]);

        DB::table('drive_requests')->insert([
            'id' => 41,
            'user_id' => $driveUser->id,
            'package_id' => null,
            'operator' => 'Grameenphone',
            'mobile' => '01722222222',
            'amount' => 50,
            'status' => 'pending',
            'balance_type' => 'main_bal',
            'admin_status' => 'waiting',
            'admin_note' => 'Review',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            'id' => 42,
            'user_id' => $regularUser->id,
            'package_id' => null,
            'operator' => 'Robi',
            'mobile' => '01822222222',
            'amount' => 40,
            'status' => 'pending',
            'admin_status' => 'process',
            'admin_note' => 'Processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/admin/pending-drive-requests')
            ->post(route('admin.pending.requests.bulk-action'), [
                'bulk_action' => 'cancel',
                'bulk_note' => 'Cancelled in bulk',
                'pin' => '1234',
                'request_keys' => ['drive:41', 'internet:42'],
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Selected pending requests updated successfully.');

        $this->assertSame('rejected', DB::table('drive_requests')->where('id', 41)->value('status'));
        $this->assertSame(65.0, (float) DB::table('users')->where('id', 247)->value('main_bal'));
        $this->assertSame(5.0, (float) DB::table('users')->where('id', 247)->value('drive_bal'));
        $this->assertSame('rejected', DB::table('regular_requests')->where('id', 42)->value('status'));
        $this->assertSame(60.0, (float) DB::table('users')->where('id', 248)->value('main_bal'));
    }

    public function test_admin_can_bulk_sync_routed_provider_requests_without_local_refunds(): void
    {
        $this->ensureAdminBalanceColumnsAndHistoryTable();
        $this->ensureDriveHistoryTable();
        $this->ensureProviderApiDriveTables();
        $this->ensureProviderApiInternetTables();

        $admin = $this->createLoginUser(249, [
            'name' => 'Bulk Routed Admin',
            'email' => 'bulk-routed-admin@example.com',
            'is_admin' => true,
        ]);

        $driveApproveUser = $this->createLoginUser(250, [
            'name' => 'Bulk Routed Drive Approve User',
            'email' => 'bulk-routed-drive-approve@example.com',
            'main_bal' => 40,
            'drive_bal' => 20,
        ]);

        $regularApproveUser = $this->createLoginUser(251, [
            'name' => 'Bulk Routed Internet Approve User',
            'email' => 'bulk-routed-internet-approve@example.com',
            'main_bal' => 60,
            'drive_bal' => 5,
        ]);

        $driveCancelUser = $this->createLoginUser(252, [
            'name' => 'Bulk Routed Drive Cancel User',
            'email' => 'bulk-routed-drive-cancel@example.com',
            'main_bal' => 80,
            'drive_bal' => 15,
        ]);

        $regularCancelUser = $this->createLoginUser(253, [
            'name' => 'Bulk Routed Internet Cancel User',
            'email' => 'bulk-routed-internet-cancel@example.com',
            'main_bal' => 90,
            'drive_bal' => 10,
        ]);

        Http::fake([
            'https://source-bulk.example.test/api/v1/routed-settlement' => Http::response(['status' => 'success'], 200),
        ]);

        DB::table('drive_requests')->insert([
            [
                'id' => 51,
                'user_id' => $driveApproveUser->id,
                'package_id' => null,
                'operator' => 'Grameenphone',
                'mobile' => '01766666661',
                'amount' => 55,
                'status' => 'pending',
                'balance_type' => 'drive_bal',
                'admin_status' => 'process',
                'admin_note' => 'Queued',
                'is_routed' => true,
                'route_api_id' => 1,
                'remote_request_id' => 'provider-drive-51',
                'source_request_id' => '701',
                'source_request_type' => 'drive',
                'source_api_key' => 'bulk-source-key',
                'source_callback_url' => 'https://source-bulk.example.test/api/v1/routed-settlement',
                'source_client_domain' => 'source-bulk.example.test',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 52,
                'user_id' => $driveCancelUser->id,
                'package_id' => null,
                'operator' => 'Banglalink',
                'mobile' => '01966666662',
                'amount' => 25,
                'status' => 'pending',
                'balance_type' => 'main_bal',
                'admin_status' => 'waiting',
                'admin_note' => 'Review',
                'is_routed' => true,
                'route_api_id' => 1,
                'remote_request_id' => 'provider-drive-52',
                'source_request_id' => '702',
                'source_request_type' => 'drive',
                'source_api_key' => 'bulk-source-key',
                'source_callback_url' => 'https://source-bulk.example.test/api/v1/routed-settlement',
                'source_client_domain' => 'source-bulk.example.test',
                'created_at' => now()->addSecond(),
                'updated_at' => now()->addSecond(),
            ],
        ]);

        DB::table('regular_requests')->insert([
            [
                'id' => 61,
                'user_id' => $regularApproveUser->id,
                'package_id' => null,
                'operator' => 'Robi',
                'mobile' => '01866666661',
                'amount' => 45,
                'status' => 'pending',
                'description' => null,
                'admin_status' => 'waiting',
                'admin_note' => 'Queued',
                'is_routed' => true,
                'route_api_id' => 1,
                'remote_request_id' => 'provider-internet-61',
                'source_request_id' => '703',
                'source_request_type' => 'internet',
                'source_api_key' => 'bulk-source-key',
                'source_callback_url' => 'https://source-bulk.example.test/api/v1/routed-settlement',
                'source_client_domain' => 'source-bulk.example.test',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 62,
                'user_id' => $regularCancelUser->id,
                'package_id' => null,
                'operator' => 'Teletalk',
                'mobile' => '01566666662',
                'amount' => 35,
                'status' => 'pending',
                'description' => null,
                'admin_status' => 'process',
                'admin_note' => 'Processing',
                'is_routed' => true,
                'route_api_id' => 1,
                'remote_request_id' => 'provider-internet-62',
                'source_request_id' => '704',
                'source_request_type' => 'internet',
                'source_api_key' => 'bulk-source-key',
                'source_callback_url' => 'https://source-bulk.example.test/api/v1/routed-settlement',
                'source_client_domain' => 'source-bulk.example.test',
                'created_at' => now()->addSecond(),
                'updated_at' => now()->addSecond(),
            ],
        ]);

        $this->actingAs($admin)
            ->from('/admin/pending-drive-requests')
            ->post(route('admin.pending.requests.bulk-action'), [
                'bulk_action' => 'manual_complete',
                'bulk_note' => 'Bulk routed note',
                'pin' => '1234',
                'request_keys' => ['drive:51', 'internet:61'],
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Selected pending requests updated successfully.');

        $this->actingAs($admin)
            ->from('/admin/pending-drive-requests')
            ->post(route('admin.pending.requests.bulk-action'), [
                'bulk_action' => 'cancel',
                'bulk_note' => 'Cancel routed note',
                'pin' => '1234',
                'request_keys' => ['drive:52', 'internet:62'],
            ])
            ->assertRedirect('/admin/pending-drive-requests')
            ->assertSessionHas('success', 'Selected pending requests updated successfully.');

        $this->assertSame('approved', DB::table('drive_requests')->where('id', 51)->value('status'));
        $this->assertNull(DB::table('drive_requests')->where('id', 51)->value('admin_status'));
        $this->assertSame('approved', DB::table('regular_requests')->where('id', 61)->value('status'));
        $this->assertSame('Bulk routed note', DB::table('regular_requests')->where('id', 61)->value('description'));
        $this->assertNull(DB::table('regular_requests')->where('id', 61)->value('admin_status'));
        $this->assertSame('rejected', DB::table('drive_requests')->where('id', 52)->value('status'));
        $this->assertSame('rejected', DB::table('regular_requests')->where('id', 62)->value('status'));

        $this->assertSame(40.0, (float) $driveApproveUser->fresh()->main_bal);
        $this->assertSame(20.0, (float) $driveApproveUser->fresh()->drive_bal);
        $this->assertSame(60.0, (float) $regularApproveUser->fresh()->main_bal);
        $this->assertSame(80.0, (float) $driveCancelUser->fresh()->main_bal);
        $this->assertSame(15.0, (float) $driveCancelUser->fresh()->drive_bal);
        $this->assertSame(90.0, (float) $regularCancelUser->fresh()->main_bal);

        $this->assertDatabaseHas('drive_history', [
            'user_id' => $driveApproveUser->id,
            'status' => 'success',
            'description' => 'Bulk routed note',
        ]);

        Http::assertSentCount(4);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return (($request->data()['source_request_id'] ?? null) === 701)
                && (($request->data()['request_type'] ?? null) === 'drive')
                && (($request->data()['status'] ?? null) === 'approved')
                && (($request->data()['description'] ?? null) === 'Bulk routed note');
        });
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return (($request->data()['source_request_id'] ?? null) === 703)
                && (($request->data()['request_type'] ?? null) === 'internet')
                && (($request->data()['status'] ?? null) === 'approved')
                && (($request->data()['description'] ?? null) === 'Bulk routed note');
        });
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return (($request->data()['source_request_id'] ?? null) === 702)
                && (($request->data()['request_type'] ?? null) === 'drive')
                && (($request->data()['status'] ?? null) === 'cancelled');
        });
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return (($request->data()['source_request_id'] ?? null) === 704)
                && (($request->data()['request_type'] ?? null) === 'internet')
                && (($request->data()['status'] ?? null) === 'cancelled');
        });
    }

    public function test_admin_internet_history_shows_latest_requests_first(): void
    {
        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('regular_packages')->insert([
            'id' => 1,
            'operator' => 'Grameenphone',
            'name' => 'History Package',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createLoginUser(215, [
            'name' => 'History Admin',
            'email' => 'history-admin@example.com',
            'is_admin' => true,
        ]);

        $historyUser = $this->createLoginUser(216, [
            'name' => 'History User',
            'email' => 'history-user@example.com',
        ]);

        DB::table('regular_requests')->insert([
            [
                'id' => 1,
                'user_id' => $historyUser->id,
                'package_id' => 1,
                'operator' => 'Older Operator',
                'mobile' => '01711111111',
                'amount' => 50,
                'status' => 'approved',
                'description' => 'Older internet history row',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'id' => 2,
                'user_id' => $historyUser->id,
                'package_id' => 1,
                'operator' => 'Latest Operator',
                'mobile' => '01822222222',
                'amount' => 70,
                'status' => 'approved',
                'description' => 'Latest internet history row',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($admin)->get('/admin/internet-history');

        $response->assertOk();

        $content = $response->getContent();
        $latestPosition = strpos($content, 'Latest internet history row');
        $olderPosition = strpos($content, 'Older internet history row');

        $this->assertNotFalse($latestPosition);
        $this->assertNotFalse($olderPosition);
        $this->assertLessThan($olderPosition, $latestPosition);
    }

    public function test_admin_internet_history_includes_cancelled_rows_with_warning_badge_and_excludes_pending_requests(): void
    {
        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('regular_packages')->insert([
            'id' => 3,
            'operator' => 'Robi',
            'name' => 'Cancelled Internet Package',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createLoginUser(224, [
            'name' => 'Cancelled Internet Admin',
            'email' => 'cancelled-internet-admin@example.com',
            'is_admin' => true,
        ]);

        $historyUser = $this->createLoginUser(225, [
            'name' => 'Cancelled Internet User',
            'email' => 'cancelled-internet-user@example.com',
        ]);

        DB::table('regular_requests')->insert([
            [
                'id' => 3,
                'user_id' => $historyUser->id,
                'package_id' => 3,
                'operator' => 'Robi',
                'mobile' => '01811112222',
                'amount' => 55,
                'status' => 'cancelled',
                'description' => 'Cancelled admin internet history row',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'user_id' => $historyUser->id,
                'package_id' => 3,
                'operator' => 'Robi',
                'mobile' => '01833334444',
                'amount' => 60,
                'status' => 'pending',
                'description' => 'Pending admin internet history row',
                'created_at' => now()->addSecond(),
                'updated_at' => now()->addSecond(),
            ],
        ]);

        $response = $this->actingAs($admin)->get('/admin/internet-history');

        $response->assertOk();
        $response->assertSee('Cancelled admin internet history row');
        $response->assertSee('01811112222');
        $response->assertSee('Cancelled');
        $response->assertSee('badge-warning', false);
        $response->assertDontSee('Pending admin internet history row');
        $response->assertDontSee('01833334444');
    }

    public function test_admin_all_history_page_shows_reverse_sl_numbers(): void
    {
        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('success');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('recharge_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $admin = $this->createLoginUser(217, [
            'name' => 'All History Admin',
            'email' => 'all-history-admin@example.com',
            'is_admin' => true,
            'main_bal' => 1000,
        ]);

        $historyUser = $this->createLoginUser(218, [
            'name' => 'All History User',
            'email' => 'all-history-user@example.com',
            'main_bal' => 500,
        ]);

        DB::table('regular_packages')->insert([
            [
                'id' => 1,
                'operator' => 'Grameenphone',
                'name' => 'Older All History Package',
                'price' => 100,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'id' => 2,
                'operator' => 'Robi',
                'name' => 'Latest All History Package',
                'price' => 110,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('regular_requests')->insert([
            [
                'id' => 1,
                'user_id' => $historyUser->id,
                'package_id' => 1,
                'operator' => 'Grameenphone',
                'mobile' => '01711111111',
                'amount' => 90,
                'status' => 'approved',
                'description' => 'Older all history row',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'id' => 2,
                'user_id' => $historyUser->id,
                'package_id' => 2,
                'operator' => 'Robi',
                'mobile' => '01822222222',
                'amount' => 95,
                'status' => 'approved',
                'description' => 'Latest all history row',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($admin)->get('/admin/all-history');

        $response->assertOk();
        $this->assertMatchesRegularExpression('/<td>2<\/td>\s*<td>All History User<\/td>[\s\S]*Latest all history row[\s\S]*<td>1<\/td>\s*<td>All History User<\/td>[\s\S]*Older all history row/s', $response->getContent());
    }

    public function test_admin_all_history_shows_older_entries_by_default_without_prefilled_dates(): void
    {
        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('success');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('recharge_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $admin = $this->createLoginUser(219, [
            'name' => 'Today Filter Admin',
            'email' => 'today-filter-admin@example.com',
            'is_admin' => true,
            'main_bal' => 900,
        ]);

        $historyUser = $this->createLoginUser(220, [
            'name' => 'Today Filter User',
            'email' => 'today-filter-user@example.com',
            'main_bal' => 450,
        ]);

        DB::table('regular_packages')->insert([
            'id' => 10,
            'operator' => 'Robi',
            'name' => 'Today Package',
            'price' => 110,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            [
                'id' => 10,
                'user_id' => $historyUser->id,
                'package_id' => 10,
                'operator' => 'Robi',
                'mobile' => '01812345678',
                'amount' => 95,
                'status' => 'approved',
                'description' => 'Today admin history row',
                'created_at' => now()->startOfDay()->addHours(10),
                'updated_at' => now()->startOfDay()->addHours(10),
            ],
            [
                'id' => 11,
                'user_id' => $historyUser->id,
                'package_id' => 10,
                'operator' => 'Robi',
                'mobile' => '01812345679',
                'amount' => 96,
                'status' => 'approved',
                'description' => 'Yesterday admin history row',
                'created_at' => now()->subDay()->startOfDay()->addHours(10),
                'updated_at' => now()->subDay()->startOfDay()->addHours(10),
            ],
        ]);

        $response = $this->actingAs($admin)->get('/admin/all-history');

        $response->assertOk();
        $response->assertSee('Today admin history row');
        $response->assertSee('Yesterday admin history row');
        $response->assertSee('name="date_from" value=""', false);
        $response->assertSee('name="date_to" value=""', false);
    }

    public function test_admin_all_history_includes_flexi_requests_and_supports_flexi_filter(): void
    {
        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        $this->ensureFlexiRequestsTable();

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('success');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('recharge_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $admin = $this->createLoginUser(222, [
            'name' => 'Flexi History Admin',
            'email' => 'flexi-history-admin@example.com',
            'is_admin' => true,
            'main_bal' => 1000,
        ]);

        $historyUser = $this->createLoginUser(223, [
            'name' => 'Flexi History User',
            'email' => 'flexi-history-user@example.com',
            'main_bal' => 540,
        ]);

        DB::table('flexi_requests')->insert([
            [
                'id' => 11,
                'user_id' => $historyUser->id,
                'operator' => 'Grameenphone',
                'mobile' => '01788888888',
                'amount' => 120,
                'cost' => 120,
                'type' => 'Prepaid',
                'trnx_id' => 'FLX-HIS-001',
                'status' => 'approved',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'id' => 12,
                'user_id' => $historyUser->id,
                'operator' => 'Robi',
                'mobile' => '01899999999',
                'amount' => 80,
                'cost' => 80,
                'type' => 'Postpaid',
                'trnx_id' => null,
                'status' => 'rejected',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('regular_requests')->insert([
            'id' => 50,
            'user_id' => $historyUser->id,
            'package_id' => null,
            'operator' => 'Banglalink',
            'mobile' => '01977777777',
            'amount' => 60,
            'status' => 'approved',
            'description' => 'Internet history row',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        $response = $this->actingAs($admin)->get('/admin/all-history');

        $response->assertOk();
        $response->assertSee('Flexi');
        $response->assertSee('Prepaid Flexiload');
        $response->assertSee('Postpaid Flexiload');
        $response->assertSee('01788888888');
        $response->assertSee('01899999999');

        $filteredResponse = $this->actingAs($admin)->get('/admin/all-history?service=flexi');

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Prepaid Flexiload');
        $filteredResponse->assertSee('Postpaid Flexiload');
        $filteredResponse->assertDontSee('Internet history row');
    }

    public function test_admin_all_history_includes_cancelled_internet_rows_and_excludes_pending_regular_requests(): void
    {
        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        Schema::create('drive_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('drive_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('success');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_packages', function (Blueprint $table) {
            $table->id();
            $table->string('operator')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('recharge_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $admin = $this->createLoginUser(226, [
            'name' => 'All History Cancelled Admin',
            'email' => 'all-history-cancelled-admin@example.com',
            'is_admin' => true,
            'main_bal' => 1000,
        ]);

        $historyUser = $this->createLoginUser(227, [
            'name' => 'All History Cancelled User',
            'email' => 'all-history-cancelled-user@example.com',
            'main_bal' => 450,
        ]);

        DB::table('regular_packages')->insert([
            'id' => 20,
            'operator' => 'Banglalink',
            'name' => 'Cancelled All History Package',
            'price' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regular_requests')->insert([
            [
                'id' => 20,
                'user_id' => $historyUser->id,
                'package_id' => 20,
                'operator' => 'Banglalink',
                'mobile' => '01911112222',
                'amount' => 65,
                'status' => 'cancelled',
                'description' => 'Cancelled admin all history row',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 21,
                'user_id' => $historyUser->id,
                'package_id' => 20,
                'operator' => 'Banglalink',
                'mobile' => '01933334444',
                'amount' => 66,
                'status' => 'pending',
                'description' => 'Pending admin all history row',
                'created_at' => now()->addSecond(),
                'updated_at' => now()->addSecond(),
            ],
        ]);

        $response = $this->actingAs($admin)->get('/admin/all-history');

        $response->assertOk();
        $response->assertSee('Cancelled admin all history row');
        $response->assertSee('01911112222');
        $response->assertSee('Cancelled');
        $response->assertSee('badge-warning', false);
        $response->assertDontSee('Pending admin all history row');
        $response->assertDontSee('01933334444');

        $filteredResponse = $this->actingAs($admin)->get('/admin/all-history?status=cancelled&service=internet');

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Cancelled admin all history row');
        $filteredResponse->assertDontSee('Pending admin all history row');
    }

    public function test_admin_service_modules_page_renders_database_backed_module_table(): void
    {
        $this->seedServiceModulesTable();

        $admin = $this->createLoginUser(228, [
            'name' => 'Service Module Admin',
            'email' => 'service-module-admin@example.com',
            'is_admin' => true,
        ]);

        $flexiloadId = DB::table('service_modules')->where('title', 'Flexiload')->value('id');

        $response = $this->actingAs($admin)->get(route('admin.service.modules'));

        $response->assertOk();
        $response->assertSee('Service Modules');
        $response->assertSee('Require Pin/Name/NID/Sender');
        $response->assertSee('Sort Order');
        $response->assertSee('Flexiload');
        $response->assertSee('InternetPack');
        $response->assertSee('Sonali Bank Limited');
        $response->assertSee('BillPay2');
        $response->assertSee('BPO');
        $response->assertSee(route('admin.service.modules'), false);
        $response->assertSee(route('admin.service.modules', ['edit' => $flexiloadId]), false);
        $response->assertSee(route('admin.service.modules.toggle', $flexiloadId), false);
        $response->assertSee(route('admin.service.modules.sort', $flexiloadId), false);
        $response->assertSee('Edit');
        $response->assertSee('Toggle');
        $response->assertSee('Delete');
    }

    public function test_admin_can_create_service_module_from_service_modules_page(): void
    {
        $this->ensureServiceModulesTable();

        $admin = $this->createLoginUser(229, [
            'name' => 'Service Module Creator',
            'email' => 'service-module-creator@example.com',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.service.modules'))
            ->post(route('admin.service.modules.store'), [
                'title' => 'Gift Voucher',
                'minimum_amount' => 50,
                'maximum_amount' => 2500,
                'minimum_length' => 8,
                'maximum_length' => 16,
                'auto_send_limit' => 400.00,
                'require_name' => '1',
                'sort_order' => 15,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.service.modules'));
        $response->assertSessionHas('success', 'Service module created successfully.');

        $this->assertDatabaseHas('service_modules', [
            'title' => 'Gift Voucher',
            'sort_order' => 15,
            'status' => 'active',
            'require_pin' => 0,
            'require_name' => 1,
        ]);
    }

    public function test_admin_can_edit_service_module_from_service_modules_page(): void
    {
        $this->seedServiceModulesTable();

        $admin = $this->createLoginUser(230, [
            'name' => 'Service Module Editor',
            'email' => 'service-module-editor@example.com',
            'is_admin' => true,
        ]);

        $moduleId = DB::table('service_modules')->where('title', 'Flexiload')->value('id');

        $pageResponse = $this->actingAs($admin)->get(route('admin.service.modules', ['edit' => $moduleId]));

        $pageResponse->assertOk();
        $pageResponse->assertSee('Edit Service Module');
        $pageResponse->assertSee(route('admin.service.modules.update', $moduleId), false);

        $response = $this->actingAs($admin)
            ->from(route('admin.service.modules', ['edit' => $moduleId]))
            ->put(route('admin.service.modules.update', $moduleId), [
                'title' => 'Flexiload Pro',
                'minimum_amount' => 20,
                'maximum_amount' => 1999,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 1200.00,
                'require_pin' => '1',
                'require_sender' => '1',
                'sort_order' => 3,
                'status' => 'deactive',
            ]);

        $response->assertRedirect(route('admin.service.modules'));
        $response->assertSessionHas('success', 'Service module updated successfully.');

        $updatedModule = DB::table('service_modules')->where('id', $moduleId)->first();

        $this->assertNotNull($updatedModule);
        $this->assertSame('Flexiload Pro', $updatedModule->title);
        $this->assertSame('deactive', $updatedModule->status);
        $this->assertSame(3, (int) $updatedModule->sort_order);
        $this->assertSame(1, (int) $updatedModule->require_sender);
        $this->assertEquals(1999.0, (float) $updatedModule->maximum_amount);
    }

    public function test_admin_can_toggle_and_sort_service_module_from_service_modules_page(): void
    {
        $this->seedServiceModulesTable();

        $admin = $this->createLoginUser(231, [
            'name' => 'Service Module Operator',
            'email' => 'service-module-operator@example.com',
            'is_admin' => true,
        ]);

        $moduleId = DB::table('service_modules')->where('title', 'SMS')->value('id');

        $toggleResponse = $this->actingAs($admin)
            ->post(route('admin.service.modules.toggle', $moduleId));

        $toggleResponse->assertRedirect(route('admin.service.modules'));
        $toggleResponse->assertSessionHas('success', 'Service module status updated successfully.');
        $this->assertSame('deactive', DB::table('service_modules')->where('id', $moduleId)->value('status'));

        $sortResponse = $this->actingAs($admin)
            ->post(route('admin.service.modules.sort', $moduleId), [
                'sort_order' => 22,
            ]);

        $sortResponse->assertRedirect(route('admin.service.modules'));
        $sortResponse->assertSessionHas('success', 'Service module sort order updated successfully.');
        $this->assertSame(22, (int) DB::table('service_modules')->where('id', $moduleId)->value('sort_order'));
    }

    public function test_admin_can_delete_service_module_from_service_modules_page(): void
    {
        $this->seedServiceModulesTable();

        $admin = $this->createLoginUser(232, [
            'name' => 'Service Module Deleter',
            'email' => 'service-module-deleter@example.com',
            'is_admin' => true,
        ]);

        $moduleId = DB::table('service_modules')->where('title', 'BPO')->value('id');

        $response = $this->actingAs($admin)
            ->delete(route('admin.service.modules.destroy', $moduleId));

        $response->assertRedirect(route('admin.service.modules'));
        $response->assertSessionHas('success', 'Service module deleted successfully.');
        $this->assertDatabaseMissing('service_modules', ['id' => $moduleId]);
    }

    public function test_user_all_history_defaults_to_today_only_and_old_data_can_be_filtered(): void
    {
        $this->ensureFlexiRequestsTable();

        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('success');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $user = $this->createLoginUser(221, [
            'name' => 'User History Viewer',
            'email' => 'user-history-viewer@example.com',
        ]);

        DB::table('drive_history')->insert([
            [
                'user_id' => $user->id,
                'package_id' => null,
                'operator' => 'Drive',
                'mobile' => '01911111111',
                'amount' => 50,
                'status' => 'success',
                'description' => 'Today user history row',
                'created_at' => now()->startOfDay()->addHours(10),
                'updated_at' => now()->startOfDay()->addHours(10),
            ],
            [
                'user_id' => $user->id,
                'package_id' => null,
                'operator' => 'Drive',
                'mobile' => '01922222222',
                'amount' => 60,
                'status' => 'success',
                'description' => 'Old user history row',
                'created_at' => now()->subDay()->startOfDay()->addHours(10),
                'updated_at' => now()->subDay()->startOfDay()->addHours(10),
            ],
        ]);

        DB::table('regular_requests')->insert([
            [
                'user_id' => $user->id,
                'package_id' => null,
                'operator' => 'Grameenphone',
                'mobile' => '01755555555',
                'amount' => 75,
                'status' => 'approved',
                'description' => 'Today internet history row',
                'created_at' => now()->startOfDay()->addHours(11),
                'updated_at' => now()->startOfDay()->addHours(11),
            ],
            [
                'user_id' => $user->id,
                'package_id' => null,
                'operator' => 'Robi',
                'mobile' => '01899999999',
                'amount' => 55,
                'status' => 'cancelled',
                'description' => 'Today cancelled internet history row',
                'created_at' => now()->startOfDay()->addHours(11)->addMinutes(30),
                'updated_at' => now()->startOfDay()->addHours(11)->addMinutes(30),
            ],
        ]);

        DB::table('flexi_requests')->insert([
            [
                'id' => 21,
                'user_id' => $user->id,
                'operator' => 'Robi',
                'mobile' => '01866666666',
                'amount' => 85,
                'cost' => 85,
                'type' => 'Prepaid',
                'trnx_id' => 'FLX-USER-001',
                'status' => 'approved',
                'created_at' => now()->startOfDay()->addHours(12),
                'updated_at' => now()->startOfDay()->addHours(12),
            ],
            [
                'id' => 22,
                'user_id' => $user->id,
                'operator' => 'Airtel',
                'mobile' => '01677777777',
                'amount' => 65,
                'cost' => 65,
                'type' => 'Postpaid',
                'trnx_id' => null,
                'status' => 'rejected',
                'created_at' => now()->subDay()->startOfDay()->addHours(12),
                'updated_at' => now()->subDay()->startOfDay()->addHours(12),
            ],
        ]);

        $todayResponse = $this->actingAs($user)->get('/my-history');

        $todayResponse->assertOk();
        $todayResponse->assertSee('Today user history row');
        $todayResponse->assertSee('Internet Pack Recharge');
        $todayResponse->assertSee('Internet Pack Request Cancelled');
        $todayResponse->assertSee('01899999999');
        $todayResponse->assertSee('Flexi');
        $todayResponse->assertSee('Prepaid Flexiload');
        $todayResponse->assertSee('01866666666');
        $todayResponse->assertDontSee('Old user history row');
        $todayResponse->assertDontSee('01677777777');
        $todayResponse->assertSee('History Filter');
        $todayResponse->assertSee('value="' . now()->toDateString() . '"', false);

        $filteredResponse = $this->actingAs($user)->get('/my-history?date_from=' . now()->subDay()->toDateString() . '&date_to=' . now()->subDay()->toDateString());

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Old user history row');
        $filteredResponse->assertSee('Postpaid Flexiload');
        $filteredResponse->assertSee('01677777777');
        $filteredResponse->assertDontSee('Today user history row');
        $filteredResponse->assertDontSee('01899999999');
        $filteredResponse->assertDontSee('01866666666');
    }

    public function test_user_drive_history_includes_cancelled_internet_requests_with_cancelled_badge(): void
    {
        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('success');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('regular_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $user = $this->createLoginUser(222, [
            'name' => 'Drive History Viewer',
            'email' => 'drive-history-viewer@example.com',
        ]);

        $otherUser = $this->createLoginUser(223, [
            'name' => 'Other History User',
            'email' => 'other-history-user@example.com',
        ]);

        DB::table('drive_history')->insert([
            'user_id' => $user->id,
            'package_id' => null,
            'operator' => 'Drive',
            'mobile' => '01911111111',
            'amount' => 50,
            'status' => 'success',
            'description' => 'Drive Recharge',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        DB::table('regular_requests')->insert([
            [
                'user_id' => $user->id,
                'package_id' => null,
                'operator' => 'Banglalink',
                'mobile' => '01977777777',
                'amount' => 60,
                'status' => 'cancelled',
                'description' => 'Cancelled internet history row',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $otherUser->id,
                'package_id' => null,
                'operator' => 'Robi',
                'mobile' => '01800000000',
                'amount' => 70,
                'status' => 'cancelled',
                'description' => 'Other cancelled history row',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->get('/my-drive-history');

        $response->assertOk();
        $response->assertSee('01977777777');
        $response->assertSee('Internet Pack Request Cancelled');
        $response->assertSee('Cancelled');
        $response->assertSee('badge-warning', false);
        $response->assertDontSee('01800000000');
    }

    public function test_internet_purchase_rejects_invalid_user_pin(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('regular_packages')) {
            Schema::create('regular_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 301,
            'name' => 'Internet Buyer',
            'email' => 'internetbuyer@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['internet']),
            'main_bal' => 500,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('regular_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => '10GB Internet Pack',
            'price' => 200,
            'commission' => 20,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(301);

        $this->actingAs($user)
            ->postJson('/internet-packs/Grameenphone/buy/' . $packageId, [
                'mobile' => '01712345678',
                'pin' => '9999',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid PIN',
            ]);

        $this->assertSame(0, DB::table('regular_requests')->count());
        $this->assertSame(500.0, (float) DB::table('users')->where('id', 301)->value('main_bal'));
    }

    public function test_internet_purchase_rejects_invalid_operator_prefix(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('regular_packages')) {
            Schema::create('regular_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 302,
            'name' => 'Internet Prefix Buyer',
            'email' => 'internetprefix@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['internet']),
            'main_bal' => 500,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('regular_packages')->insertGetId([
            'operator' => 'Robi',
            'name' => '15GB Internet Pack',
            'price' => 300,
            'commission' => 30,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(302);

        $this->actingAs($user)
            ->postJson('/internet-packs/Robi/buy/' . $packageId, [
                'mobile' => '01712345678',
                'pin' => '1234',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid mobile number for selected operator',
            ]);

        $this->assertSame(0, DB::table('regular_requests')->count());
        $this->assertSame(500.0, (float) DB::table('users')->where('id', 302)->value('main_bal'));
    }

    public function test_internet_purchase_accepts_correct_pin_and_operator_prefix(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('regular_packages')) {
            Schema::create('regular_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 303,
            'name' => 'Internet Buyer Success',
            'email' => 'internetbuyer-success@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['internet']),
            'main_bal' => 500,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('regular_packages')->insertGetId([
            'operator' => 'Banglalink',
            'name' => '20GB Internet Pack',
            'price' => 350,
            'commission' => 50,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(303);

        $this->actingAs($user)
            ->postJson('/internet-packs/Banglalink/buy/' . $packageId, [
                'mobile' => '01912345678',
                'pin' => '1234',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSame(1, DB::table('regular_requests')->where('user_id', 303)->count());
        $this->assertSame('pending', DB::table('regular_requests')->where('user_id', 303)->value('status'));
        $this->assertSame(200.0, (float) DB::table('users')->where('id', 303)->value('main_bal'));
    }

    public function test_internet_purchase_rejects_when_main_balance_is_insufficient(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('regular_packages')) {
            Schema::create('regular_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 306,
            'name' => 'Low Main Balance User',
            'email' => 'low-main@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['internet']),
            'main_bal' => 40,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('regular_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Large Internet Pack',
            'price' => 120,
            'commission' => 10,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(306);

        $this->actingAs($user)
            ->postJson('/internet-packs/Grameenphone/buy/' . $packageId, [
                'mobile' => '01712345678',
                'pin' => '1234',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient main balance',
            ]);

        $this->assertSame(0, DB::table('regular_requests')->where('user_id', 306)->count());
        $this->assertSame(40.0, (float) DB::table('users')->where('id', 306)->value('main_bal'));
    }

    public function test_internet_purchase_succeeds_when_fcm_token_column_is_missing(): void
    {
        if (!Schema::hasColumn('users', 'pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (Schema::hasColumn('users', 'fcm_token_updated_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fcm_token_updated_at');
            });
        }

        if (Schema::hasColumn('users', 'fcm_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        if (!Schema::hasTable('regular_packages')) {
            Schema::create('regular_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 307,
            'name' => 'No Fcm Column Buyer',
            'email' => 'nofcm@example.com',
            'password' => 'secret',
            'pin' => Hash::make('1234'),
            'permissions' => json_encode(['internet']),
            'main_bal' => 500,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('regular_packages')->insertGetId([
            'operator' => 'Robi',
            'name' => 'No Fcm Internet Pack',
            'price' => 150,
            'commission' => 10,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(307);

        $this->actingAs($user)
            ->postJson('/internet-packs/Robi/buy/' . $packageId, [
                'mobile' => '01812345678',
                'pin' => '1234',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSame(1, DB::table('regular_requests')->where('user_id', 307)->count());
        $this->assertSame(360.0, (float) DB::table('users')->where('id', 307)->value('main_bal'));
    }

    public function test_drive_confirm_page_renders_balance_values_for_purchase_script(): void
    {
        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        DB::table('brandings')->insert([
            'id' => 1,
            'drive_balance' => 'on',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 304,
            'name' => 'Drive Confirm User',
            'email' => 'drive-confirm@example.com',
            'password' => 'secret',
            'permissions' => json_encode(['drive']),
            'main_bal' => 700,
            'drive_bal' => 500,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Drive Confirm Package',
            'price' => 300,
            'commission' => 20,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(304);

        $this->actingAs($user)
            ->get('/drive-offers/Grameenphone/confirm/' . $packageId . '?mobile=01712345678&pin=1234')
            ->assertOk()
            ->assertSee('&#2547;280', false)
            ->assertDontSee('à§³', false)
            ->assertSee('const availableBalance = 500', false)
            ->assertSee('const selectedBalanceLabel = "drive balance"', false)
            ->assertSee('const packagePrice = 280', false);
    }

    public function test_drive_confirm_page_uses_main_balance_values_when_drive_balance_setting_is_off(): void
    {
        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        DB::table('brandings')->insert([
            'id' => 1,
            'drive_balance' => 'off',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 308,
            'name' => 'Drive Confirm Main Balance User',
            'email' => 'drive-confirm-main@example.com',
            'password' => 'secret',
            'permissions' => json_encode(['drive']),
            'main_bal' => 600,
            'drive_bal' => 40,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Drive Confirm Main Balance Package',
            'price' => 320,
            'commission' => 20,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(308);

        $this->actingAs($user)
            ->get('/drive-offers/Grameenphone/confirm/' . $packageId . '?mobile=01712345678&pin=1234')
            ->assertOk()
            ->assertSee('const availableBalance = 600', false)
            ->assertSee('const selectedBalanceLabel = "main balance"', false)
            ->assertSee('const packagePrice = 300', false)
            ->assertDontSee('const availableBalance = 40', false);
    }

    public function test_internet_confirm_page_renders_balance_values_for_purchase_script(): void
    {
        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('regular_packages')) {
            Schema::create('regular_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 305,
            'name' => 'Internet Confirm User',
            'email' => 'internet-confirm@example.com',
            'password' => 'secret',
            'permissions' => json_encode(['internet']),
            'main_bal' => 600,
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('regular_packages')->insertGetId([
            'operator' => 'Robi',
            'name' => 'Internet Confirm Package',
            'price' => 250,
            'commission' => 10,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(305);

        $this->actingAs($user)
            ->get('/internet-packs/Robi/confirm/' . $packageId . '?mobile=01812345678&pin=1234')
            ->assertOk()
            ->assertSee('&#2547;240', false)
            ->assertDontSee('à§³', false)
            ->assertSee('const userMainBalance = 600', false)
            ->assertSee('const packagePrice = 240', false);
    }

    public function test_internet_buy_page_renders_numeric_only_mobile_warning_script(): void
    {
        if (!Schema::hasTable('regular_packages')) {
            Schema::create('regular_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 308,
            'name' => 'Internet Buy Page User',
            'email' => 'internet-buy-page@example.com',
            'password' => 'secret',
            'permissions' => json_encode(['internet']),
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('regular_packages')->insertGetId([
            'operator' => 'Robi',
            'name' => 'Buy Page Internet Pack',
            'price' => 100,
            'commission' => 5,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(308);

        $this->actingAs($user)
            ->get('/internet-packs/Robi/buy/' . $packageId)
            ->assertOk()
            ->assertSee('Only numbers are allowed in mobile number', false)
            ->assertSee('const hadInvalidCharacters = originalValue !== sanitizedValue;', false);
    }

    public function test_drive_buy_page_renders_numeric_only_mobile_warning_script(): void
    {
        if (!Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        DB::table('users')->insert([
            'id' => 309,
            'name' => 'Drive Buy Page User',
            'email' => 'drive-buy-page@example.com',
            'password' => 'secret',
            'permissions' => json_encode(['drive']),
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageId = DB::table('drive_packages')->insertGetId([
            'operator' => 'Grameenphone',
            'name' => 'Buy Page Drive Pack',
            'price' => 100,
            'commission' => 5,
            'expire' => '2026-12-31',
            'status' => 'active',
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(309);

        $this->actingAs($user)
            ->get('/drive-offers/Grameenphone/buy/' . $packageId)
            ->assertOk()
            ->assertSee('Only numbers are allowed in mobile number', false)
            ->assertSee('const hadInvalidCharacters = originalValue !== sanitizedValue;', false)
            ->assertSee("const currentOperator = 'Grameenphone'.toLowerCase().replace(/[^a-z]/g, '');", false);
    }

    public function test_firebase_service_skips_admin_query_when_fcm_token_column_is_missing(): void
    {
        if (Schema::hasColumn('users', 'fcm_token_updated_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fcm_token_updated_at');
            });
        }

        if (Schema::hasColumn('users', 'fcm_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        DB::table('users')->insert([
            'id' => 310,
            'name' => 'Admin Without Fcm Column',
            'email' => 'admin-no-fcm@example.com',
            'password' => 'secret',
            'is_admin' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(FirebasePushNotificationService::class)->sendToAdmins('Test title', 'Test body');

        $this->assertTrue(true);
    }

    public function test_authenticated_user_receives_enabled_firebase_bootstrap_when_config_exists(): void
    {
        DB::table('homepage_settings')->insert([
            'firebase_api_key' => 'demo-api-key',
            'firebase_project_id' => 'demo-project',
            'firebase_messaging_sender_id' => '1234567890',
            'firebase_app_id' => '1:1234567890:web:abc',
            'firebase_vapid_key' => 'demo-vapid-key',
        ]);

        $user = new User();
        $user->forceFill([
            'id' => 2,
            'name' => 'Demo User',
            'is_admin' => 0,
        ]);

        $this->actingAs($user)
            ->getJson('/notifications/firebase/bootstrap')
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('config.apiKey', 'demo-api-key')
            ->assertJsonPath('vapidKey', 'demo-vapid-key');
    }

    public function test_authenticated_user_can_store_fcm_token(): void
    {
        DB::table('users')->insert([
            'id' => 5,
            'name' => 'Push User',
            'email' => 'push@example.com',
            'password' => 'secret',
            'is_admin' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(5);

        $this->actingAs($user)
            ->postJson('/notifications/firebase/token', [
                'fcm_token' => 'demo-fcm-token',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('demo-fcm-token', DB::table('users')->where('id', 5)->value('fcm_token'));
    }

    private function createLoginUser(int $id, array $overrides = []): User
    {
        $isAdmin = (bool) ($overrides['is_admin'] ?? false);
        $defaultPermissions = $isAdmin
            ? array_keys(User::adminPermissionOptions())
            : array_keys(User::resellerPermissionOptions());

        $payload = array_merge([
            'id' => $id,
            'name' => 'Login User',
            'email' => 'login-user-' . $id . '@example.com',
            'username' => null,
            'password' => Hash::make('secret123'),
            'pin' => Hash::make('1234'),
            'is_admin' => false,
            'is_first_admin' => false,
            'is_active' => true,
            'permissions' => json_encode($defaultPermissions),
            'level' => 'retailer',
            'parent_id' => null,
            'main_bal' => 0,
            'drive_bal' => 0,
            'bank_bal' => 0,
            'api_key' => null,
            'api_access_enabled' => false,
            'api_services' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        if (array_key_exists('permissions', $payload) && is_array($payload['permissions'])) {
            $payload['permissions'] = json_encode($payload['permissions']);
        }

        if (array_key_exists('api_services', $payload) && is_array($payload['api_services'])) {
            $payload['api_services'] = json_encode(array_values($payload['api_services']));
        }

        DB::table('users')->insert($payload);

        return User::query()->findOrFail($id);
    }

    private function ensureServiceModulesTable(): void
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
    }

    private function seedServiceModulesTable(?array $modules = null): void
    {
        $this->ensureServiceModulesTable();

        $records = $modules ?? $this->defaultServiceModulesData();
        DB::table('service_modules')->delete();

        $timestamp = now();
        DB::table('service_modules')->insert(array_map(function (array $module) use ($timestamp) {
            return array_merge($module, [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }, $records));
    }

    private function defaultServiceModulesData(): array
    {
        return [
            [
                'title' => 'Flexiload',
                'minimum_amount' => 10,
                'maximum_amount' => 1499,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 1000.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'title' => 'InternetPack',
                'minimum_amount' => 5,
                'maximum_amount' => 5000,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 296.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'title' => 'SMS',
                'minimum_amount' => 1,
                'maximum_amount' => 2000,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 500.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 4,
                'status' => 'active',
            ],
            [
                'title' => 'Internet Banking',
                'minimum_amount' => 500,
                'maximum_amount' => 1000000,
                'minimum_length' => 11,
                'maximum_length' => 20,
                'auto_send_limit' => 20000.00,
                'require_pin' => false,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => true,
                'sort_order' => 5,
                'status' => 'active',
            ],
            [
                'title' => 'Billpay',
                'minimum_amount' => 50,
                'maximum_amount' => 1000,
                'minimum_length' => 5,
                'maximum_length' => 15,
                'auto_send_limit' => 300.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 5,
                'status' => 'active',
            ],
            [
                'title' => 'Sonali Bank Limited',
                'minimum_amount' => 10000,
                'maximum_amount' => 1000000,
                'minimum_length' => 3,
                'maximum_length' => 20,
                'auto_send_limit' => 25000.00,
                'require_pin' => true,
                'require_name' => true,
                'require_nid' => true,
                'require_sender' => true,
                'sort_order' => 5,
                'status' => 'active',
            ],
            [
                'title' => 'Bulk Flexi',
                'minimum_amount' => 10,
                'maximum_amount' => 5000,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 500.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 8,
                'status' => 'active',
            ],
            [
                'title' => 'GlobalFlexi',
                'minimum_amount' => 10,
                'maximum_amount' => 5000,
                'minimum_length' => 5,
                'maximum_length' => 13,
                'auto_send_limit' => 500.00,
                'require_pin' => false,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 8,
                'status' => 'active',
            ],
            [
                'title' => 'PrepaidCard',
                'minimum_amount' => 9,
                'maximum_amount' => 5000,
                'minimum_length' => 5,
                'maximum_length' => 30,
                'auto_send_limit' => 1000.00,
                'require_pin' => false,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 10,
                'status' => 'active',
            ],
            [
                'title' => 'BillPay2',
                'minimum_amount' => 10,
                'maximum_amount' => 100000,
                'minimum_length' => 3,
                'maximum_length' => 90,
                'auto_send_limit' => 5000.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 10,
                'status' => 'active',
            ],
            [
                'title' => 'BPO',
                'minimum_amount' => 1000,
                'maximum_amount' => 50000,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 10200.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => true,
                'sort_order' => 12,
                'status' => 'active',
            ],
        ];
    }

    private function currentGoogleOtpCode(string $secret): string
    {
        return app(GoogleOtpService::class)->currentCode($secret);
    }

    private function ensureAdminBalanceColumnsAndHistoryTable(): void
    {
        if (!Schema::hasColumn('users', 'main_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('main_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'drive_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('drive_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('users', 'bank_bal')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('bank_bal', 15, 2)->default(0);
            });
        }

        if (!Schema::hasTable('balance_add_history')) {
            Schema::create('balance_add_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('type')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (!Schema::hasColumn('balance_add_history', 'description')) {
            Schema::table('balance_add_history', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }
    }

    private function ensureDriveHistoryTable(): void
    {
        if (Schema::hasTable('drive_history')) {
            return;
        }

        Schema::create('drive_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('operator')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    private function addRoutedRequestColumns(Blueprint $table): void
    {
        $table->boolean('is_routed')->default(false);
        $table->unsignedBigInteger('route_api_id')->nullable();
        $table->string('remote_request_id')->nullable();
        $table->string('source_request_id')->nullable();
        $table->string('source_request_type')->nullable();
        $table->string('source_api_key')->nullable();
        $table->text('source_callback_url')->nullable();
        $table->string('source_client_domain')->nullable();
        $table->timestamp('charged_at')->nullable();
        $table->timestamp('settled_at')->nullable();
    }

    private function ensureTableColumns(string $table, array $columns): void
    {
        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $table) use ($definition) {
                    $definition($table);
                });
            }
        }
    }

    private function ensureRoutedRequestColumns(string $table): void
    {
        $this->ensureTableColumns($table, [
            'is_routed' => fn(Blueprint $table) => $table->boolean('is_routed')->default(false),
            'route_api_id' => fn(Blueprint $table) => $table->unsignedBigInteger('route_api_id')->nullable(),
            'remote_request_id' => fn(Blueprint $table) => $table->string('remote_request_id')->nullable(),
            'source_request_id' => fn(Blueprint $table) => $table->string('source_request_id')->nullable(),
            'source_request_type' => fn(Blueprint $table) => $table->string('source_request_type')->nullable(),
            'source_api_key' => fn(Blueprint $table) => $table->string('source_api_key')->nullable(),
            'source_callback_url' => fn(Blueprint $table) => $table->text('source_callback_url')->nullable(),
            'source_client_domain' => fn(Blueprint $table) => $table->string('source_client_domain')->nullable(),
            'charged_at' => fn(Blueprint $table) => $table->timestamp('charged_at')->nullable(),
            'settled_at' => fn(Blueprint $table) => $table->timestamp('settled_at')->nullable(),
        ]);
    }

    private function ensureFlexiRequestsTable(): void
    {
        if (! Schema::hasTable('flexi_requests')) {
            Schema::create('flexi_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('operator');
                $table->string('mobile', 11);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('cost', 10, 2)->default(0);
                $table->string('type')->default('Prepaid');
                $table->string('trnx_id')->nullable();
                $table->string('status')->default('pending');
                $this->addRoutedRequestColumns($table);
                $table->timestamps();
            });

            return;
        }

        $this->ensureRoutedRequestColumns('flexi_requests');
    }

    private function ensureManualPaymentRequestsTable(): void
    {
        if (Schema::hasTable('manual_payment_requests')) {
            return;
        }

        Schema::create('manual_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('method');
            $table->string('sender_number');
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    private function ensureProviderApiDriveTables(): void
    {
        if (! Schema::hasTable('drive_packages')) {
            Schema::create('drive_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('drive_requests')) {
            Schema::create('drive_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('balance_type')->nullable();
                $table->string('admin_status')->nullable();
                $table->text('admin_note')->nullable();
                $this->addRoutedRequestColumns($table);
                $table->timestamps();
            });

            return;
        }

        $this->ensureTableColumns('drive_requests', [
            'balance_type' => fn(Blueprint $table) => $table->string('balance_type')->nullable(),
            'admin_status' => fn(Blueprint $table) => $table->string('admin_status')->nullable(),
            'admin_note' => fn(Blueprint $table) => $table->text('admin_note')->nullable(),
        ]);
        $this->ensureRoutedRequestColumns('drive_requests');
    }

    private function ensureProviderApiInternetTables(): void
    {
        if (! Schema::hasTable('regular_packages')) {
            Schema::create('regular_packages', function (Blueprint $table) {
                $table->id();
                $table->string('operator');
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('commission', 10, 2);
                $table->date('expire');
                $table->string('status')->default('active');
                $table->integer('sell_today')->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('comm', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('regular_requests')) {
            Schema::create('regular_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('operator')->nullable();
                $table->string('mobile')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('balance_type')->nullable();
                $table->text('description')->nullable();
                $table->string('admin_status')->nullable();
                $table->text('admin_note')->nullable();
                $this->addRoutedRequestColumns($table);
                $table->timestamps();
            });

            return;
        }

        $this->ensureTableColumns('regular_requests', [
            'balance_type' => fn(Blueprint $table) => $table->string('balance_type')->nullable(),
            'description' => fn(Blueprint $table) => $table->text('description')->nullable(),
            'admin_status' => fn(Blueprint $table) => $table->string('admin_status')->nullable(),
            'admin_note' => fn(Blueprint $table) => $table->text('admin_note')->nullable(),
        ]);
        $this->ensureRoutedRequestColumns('regular_requests');
    }
}
