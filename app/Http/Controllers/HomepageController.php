<?php

namespace App\Http\Controllers;

use App\Models\BrandingSlide;
use App\Models\HomepageSetting;
use App\Models\Operator;
use App\Services\FirebasePushNotificationService;
use App\Services\SecurityRuntimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class HomepageController extends Controller
{
    public function index()
    {
        $settings = HomepageSetting::first();
        $apiDocs = self::providerApiDocs();
        $slides = collect();

        if (Schema::hasTable('branding_slides')) {
            $slides = BrandingSlide::query()
                ->where('is_active', true)
                ->whereNotNull('image_path')
                ->orderBy('slot_number')
                ->get();
        }

        $operators = Operator::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($operators->isEmpty()) {
            $operators = $this->defaultOperators();
        }

        $noticeData = DB::table('site_notices')->first();
        $securityRuntime = app(SecurityRuntimeService::class);
        $loginNotice = null;

        if ($securityRuntime->isPopupNoticeEnabled()) {
            $loginNotice = session('login_notice');

            if (blank($loginNotice)) {
                $loginNotice = trim((string) optional($noticeData)->notice_text);
            }
        }

        return view('welcome', compact('settings', 'operators', 'noticeData', 'loginNotice', 'apiDocs', 'slides'));
    }

    public static function providerApiDocs(): array
    {
        return [
            [
                'title' => 'Auth Check',
                'endpoint' => 'POST /api/v1/auth-check',
                'description' => 'API key, manual approval, and whitelisted domain validity check kore.',
                'service_label' => null,
                'payload' => ['api_key' => 'YOUR_API_KEY'],
            ],
            [
                'title' => 'Balance',
                'endpoint' => 'POST /api/v1/balance',
                'description' => 'Main, drive, and bank balance JSON akare return kore.',
                'service_label' => null,
                'payload' => ['api_key' => 'YOUR_API_KEY'],
            ],
            [
                'title' => 'Flexiload Recharge',
                'endpoint' => 'POST /api/v1/recharge',
                'description' => 'Pending flexi request create kore ebong main balance deduct kore.',
                'service_label' => 'Flexiload Recharge',
                'payload' => ['number' => '01712345678', 'amount' => 100, 'type' => 'Prepaid', 'operator' => 'Grameenphone'],
            ],
            [
                'title' => 'Drive',
                'endpoint' => 'POST /api/v1/drive',
                'description' => 'Selected drive package-er jonno pending request create kore.',
                'service_label' => 'Drive',
                'payload' => ['package_id' => 1, 'mobile' => '01712345678'],
            ],
            [
                'title' => 'Internet Pack',
                'endpoint' => 'POST /api/v1/internet',
                'description' => 'Selected internet package-er jonno pending request create kore.',
                'service_label' => 'Internet Pack',
                'payload' => ['package_id' => 1, 'mobile' => '01812345678'],
            ],
            [
                'title' => 'bKash Add Balance',
                'endpoint' => 'POST /api/v1/bkash',
                'description' => 'Manual payment request pending hisebe submit kore.',
                'service_label' => 'bKash',
                'payload' => ['sender_number' => '01712345678', 'transaction_id' => 'BKASH-TRX-1001', 'amount' => 500, 'note' => 'API add balance request'],
            ],
            [
                'title' => 'Nagad Add Balance',
                'endpoint' => 'POST /api/v1/nagad',
                'description' => 'Manual Nagad payment pending hisebe submit kore.',
                'service_label' => 'Nagad',
                'payload' => ['sender_number' => '01812345678', 'transaction_id' => 'NAGAD-TRX-1001', 'amount' => 500, 'note' => 'API add balance request'],
            ],
            [
                'title' => 'Rocket Add Balance',
                'endpoint' => 'POST /api/v1/rocket',
                'description' => 'Manual Rocket payment pending hisebe submit kore.',
                'service_label' => 'Rocket',
                'payload' => ['sender_number' => '01912345678', 'transaction_id' => 'ROCKET-TRX-1001', 'amount' => 500, 'note' => 'API add balance request'],
            ],
        ];
    }

    protected function defaultOperatorAttributes()
    {
        return [
            [
                'name' => 'Grameenphone',
                'short_code' => 'GP',
                'logo_text' => 'GP',
                'description' => 'Prepaid & Postpaid',
                'circle_bg_color' => '#0078C8',
                'logo_image_url' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Banglalink',
                'short_code' => 'BL',
                'logo_text' => 'BL',
                'description' => 'Prepaid & Postpaid',
                'circle_bg_color' => '#E61E25',
                'logo_image_url' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Robi',
                'short_code' => 'R',
                'logo_text' => 'R',
                'description' => 'Prepaid & Postpaid',
                'circle_bg_color' => '#E60000',
                'logo_image_url' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Airtel',
                'short_code' => 'A',
                'logo_text' => 'A',
                'description' => 'Prepaid & Postpaid',
                'circle_bg_color' => '#E60000',
                'logo_image_url' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Teletalk',
                'short_code' => 'T',
                'logo_text' => 'T',
                'description' => 'Prepaid & Postpaid',
                'circle_bg_color' => '#0066B3',
                'logo_image_url' => null,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Skitto',
                'short_code' => 'SK',
                'logo_text' => 'SK',
                'description' => 'Prepaid Only',
                'circle_bg_color' => '#FF6B00',
                'logo_image_url' => null,
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];
    }

    protected function defaultOperators()
    {
        return collect($this->defaultOperatorAttributes())
            ->map(fn(array $operator) => new Operator($operator));
    }

    protected function normalizeSelectionOperatorName(?string $name): string
    {
        return match (strtolower((string) $name)) {
            'grameenphone' => 'GrameenPhone',
            default => (string) $name,
        };
    }

    public function selectionOperators(): array
    {
        $operators = Operator::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($operators->isEmpty()) {
            $operators = $this->defaultOperators();
        }

        return $operators
            ->map(function (Operator $operator) {
                return [
                    'name' => $operator->name,
                    'route_name' => $this->normalizeSelectionOperatorName($operator->name),
                    'code' => $operator->short_code ?: $operator->logo_text ?: strtoupper(substr((string) $operator->name, 0, 2)),
                    'color' => $operator->circle_bg_color ?: '#0078C8',
                    'logo' => $operator->logo,
                    'logo_image_url' => $operator->logo_image_url,
                ];
            })
            ->values()
            ->all();
    }

    protected function ensureDefaultOperators()
    {
        foreach ($this->defaultOperatorAttributes() as $operator) {
            Operator::firstOrCreate(
                ['name' => $operator['name']],
                $operator
            );
        }

        return Operator::orderBy('sort_order')->orderBy('id')->get();
    }

    public function edit()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        $operators = $this->ensureDefaultOperators();
        return view('admin.general-settings', compact('settings', 'operators'));
    }

    public function update(Request $request)
    {
        $hasReferralColumns = Schema::hasColumn('homepage_settings', 'referral_reward_coin')
            && Schema::hasColumn('homepage_settings', 'referral_convert_coin')
            && Schema::hasColumn('homepage_settings', 'referral_convert_amount');

        $rules = [
            'logos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:4096'],
        ];

        if ($hasReferralColumns) {
            $rules['referral_reward_coin'] = ['nullable', 'integer', 'min:0'];
            $rules['referral_convert_coin'] = ['nullable', 'integer', 'min:0'];
            $rules['referral_convert_amount'] = ['nullable', 'numeric', 'min:0'];
        }

        $request->validate($rules);

        $settings = HomepageSetting::firstOrCreate([]);

        $data = $request->only([
            'page_title',
            'company_name',
            'footer_company_name',
            'footer_description',
            'footer_address',
            'footer_phone',
            'footer_email',
            'recaptcha_site_key',
            'recaptcha_secret_key',
            'social_whatsapp_url',
            'social_youtube_url',
            'social_shopee_url',
            'social_telegram_url',
            'social_messenger_url',
        ]);

        if ($hasReferralColumns) {
            $data = array_merge($data, $request->only([
                'referral_reward_coin',
                'referral_convert_coin',
                'referral_convert_amount',
            ]));
        }

        $data['recaptcha_site_key'] = filled(trim((string) ($data['recaptcha_site_key'] ?? '')))
            ? trim((string) $data['recaptcha_site_key'])
            : null;
        $data['recaptcha_secret_key'] = filled(trim((string) ($data['recaptcha_secret_key'] ?? '')))
            ? trim((string) $data['recaptcha_secret_key'])
            : null;

        if ($hasReferralColumns) {
            $data['referral_reward_coin'] = (int) ($data['referral_reward_coin'] ?? 0);
            $data['referral_convert_coin'] = (int) ($data['referral_convert_coin'] ?? 0);
            $data['referral_convert_amount'] = round((float) ($data['referral_convert_amount'] ?? 0), 2);
        }

        if ($request->hasFile('company_logo')) {
            $logo = $request->file('company_logo');
            $logoName = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('uploads'), $logoName);
            $data['company_logo_url'] = 'uploads/' . $logoName;
        }

        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon');
            $faviconName = 'favicon_' . time() . '.' . $favicon->getClientOriginalExtension();
            $favicon->move(public_path('uploads'), $faviconName);
            $data['favicon_path'] = 'uploads/' . $faviconName;
        }

        $settings->update($data);
        $this->syncOperatorLogos($request);

        return redirect()
            ->route('admin.homepage.edit')
            ->with('success', 'Settings updated successfully.');
    }

    public function mailConfig()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        return view('admin.mail-config', compact('settings'));
    }

    public function updateMailConfig(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);

        $data = $request->only([
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ]);

        $settings->update($data);

        return redirect()
            ->route('admin.mail.config')
            ->with('success', 'Mail settings updated successfully.');
    }

    public function smsConfig()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        return view('admin.sms-config', compact('settings'));
    }

    public function firebaseConfig()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        return view('admin.firebase-config', compact('settings'));
    }

    public function googleOtpConfig()
    {
        $settings = HomepageSetting::firstOrCreate([]);

        return view('admin.google-otp-config', compact('settings'));
    }

    public function recaptchaConfig()
    {
        $settings = HomepageSetting::firstOrCreate([]);

        return view('admin.recaptcha-config', compact('settings'));
    }

    public function tawkChatConfig()
    {
        $settings = HomepageSetting::firstOrCreate([]);

        return view('admin.tawk-chat-config', compact('settings'));
    }

    public function updateSmsConfig(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);

        $data = $request->only([
            'sms_api_key',
            'sms_sender_id',
            'sms_api_url',
        ]);

        $settings->update($data);

        return redirect()
            ->route('admin.sms.config')
            ->with('success', 'SMS settings updated successfully.');
    }

    public function updateFirebaseConfig(Request $request)
    {
        $validated = $request->validate([
            'firebase_api_key' => ['nullable', 'string'],
            'firebase_auth_domain' => ['nullable', 'string'],
            'firebase_project_id' => ['nullable', 'string'],
            'firebase_storage_bucket' => ['nullable', 'string'],
            'firebase_messaging_sender_id' => ['nullable', 'string'],
            'firebase_app_id' => ['nullable', 'string'],
            'firebase_vapid_key' => ['nullable', 'string'],
            'firebase_service_account_json' => ['nullable', 'string'],
        ]);

        $serviceAccountJson = trim((string) ($validated['firebase_service_account_json'] ?? ''));

        if ($serviceAccountJson !== '') {
            json_decode($serviceAccountJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()
                    ->withErrors(['firebase_service_account_json' => 'Service account JSON টি valid JSON হতে হবে।'])
                    ->withInput();
            }
        }

        $validated['firebase_service_account_json'] = $serviceAccountJson !== '' ? $serviceAccountJson : null;

        HomepageSetting::firstOrCreate([])->update($validated);

        return redirect()
            ->route('admin.firebase.config')
            ->with('success', 'Firebase settings updated successfully.');
    }

    public function updateGoogleOtpConfig(Request $request)
    {
        $validated = $request->validate([
            'google_otp_enabled' => ['nullable', 'boolean'],
            'google_otp_issuer' => ['nullable', 'string', 'max:255'],
        ]);

        $issuer = trim((string) ($validated['google_otp_issuer'] ?? ''));

        HomepageSetting::firstOrCreate([])->update([
            'google_otp_enabled' => $request->boolean('google_otp_enabled'),
            'google_otp_issuer' => $issuer !== '' ? $issuer : null,
        ]);

        return redirect()
            ->route('admin.google.otp.config')
            ->with('success', 'Google OTP settings updated successfully.');
    }

    public function updateRecaptchaConfig(Request $request)
    {
        $validated = $request->validate([
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:4096'],
        ]);

        $recaptchaSiteKey = trim((string) ($validated['recaptcha_site_key'] ?? ''));
        $recaptchaSecretKey = trim((string) ($validated['recaptcha_secret_key'] ?? ''));

        HomepageSetting::firstOrCreate([])->update([
            'recaptcha_site_key' => $recaptchaSiteKey !== '' ? $recaptchaSiteKey : null,
            'recaptcha_secret_key' => $recaptchaSecretKey !== '' ? $recaptchaSecretKey : null,
        ]);

        return redirect()
            ->route('admin.recaptcha.config')
            ->with('success', 'reCAPTCHA settings updated successfully.');
    }

    public function updateTawkChatConfig(Request $request)
    {
        $validated = $request->validate([
            'tawk_property_id' => ['nullable', 'string', 'max:255'],
            'tawk_widget_id' => ['nullable', 'string', 'max:255'],
        ]);

        $tawkPropertyId = trim((string) ($validated['tawk_property_id'] ?? ''));
        $tawkWidgetId = trim((string) ($validated['tawk_widget_id'] ?? ''));

        HomepageSetting::firstOrCreate([])->update([
            'tawk_property_id' => $tawkPropertyId !== '' ? $tawkPropertyId : null,
            'tawk_widget_id' => $tawkWidgetId !== '' ? $tawkWidgetId : null,
        ]);

        return redirect()
            ->route('admin.tawk.config')
            ->with('success', 'Tawk Chat settings updated successfully.');
    }

    public function firebaseBootstrap(FirebasePushNotificationService $pushService)
    {
        $user = auth()->user();
        $settings = HomepageSetting::first();

        return response()->json([
            'authenticated' => auth()->check(),
            'enabled' => auth()->check() && $pushService->hasWebPushConfig(),
            'config' => $pushService->webConfig($settings),
            'vapidKey' => $settings?->firebase_vapid_key,
            'userId' => $user?->id,
            'isAdmin' => (bool) ($user?->is_admin),
        ]);
    }

    public function registerFcmToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:4096'],
        ]);

        $request->user()->forceFill([
            'fcm_token' => $validated['fcm_token'],
            'fcm_token_updated_at' => now(),
        ])->save();

        return response()->json(['success' => true]);
    }

    public function firebaseMessagingServiceWorker(FirebasePushNotificationService $pushService)
    {
        $config = $pushService->webConfig();
        $enabled = $pushService->hasWebPushConfig();
        $configJson = $enabled ? json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 'null';
        $pushEnabledLiteral = $enabled ? 'true' : 'false';
        $fallbackLink = json_encode(url('/'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $script = <<<JS
const firebaseConfig = {$configJson};
const pushEnabled = {$pushEnabledLiteral};

if (pushEnabled && firebaseConfig) {
    importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js');
    importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js');

    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }

    firebase.messaging().onBackgroundMessage(function (payload) {
        const notification = payload.notification || {};
        const data = payload.data || {};

        self.registration.showNotification(notification.title || data.title || 'Notification', {
            body: notification.body || data.body || '',
            icon: payload.webpush?.notification?.icon || data.icon,
            data: {
                link: data.link || {$fallbackLink},
            },
        });
    });
}

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const targetUrl = event.notification?.data?.link || {$fallbackLink};

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
JS;

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    public function updateLogos(Request $request)
    {
        $request->validate([
            'logos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $this->syncOperatorLogos($request);

        return redirect()
            ->route('admin.homepage.edit')
            ->with('success', 'Operator logos updated successfully.');
    }

    protected function syncOperatorLogos(Request $request): void
    {
        $operators = $this->ensureDefaultOperators();

        foreach ($operators as $operator) {
            if ($request->hasFile("logos.{$operator->id}")) {
                $logo = $request->file("logos.{$operator->id}");
                $logoName = 'operator_' . $operator->id . '_' . time() . '.' . $logo->getClientOriginalExtension();
                $logo->move(public_path('uploads'), $logoName);
                $operator->logo = 'uploads/' . $logoName;
                $operator->logo_image_url = 'uploads/' . $logoName;
                $operator->save();
            }
        }
    }
}
