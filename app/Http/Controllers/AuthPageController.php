<?php

namespace App\Http\Controllers;

use App\Models\DepositSetting;
use App\Models\HomepageSetting;
use App\Models\User;
use App\Services\DeviceApprovalService;
use App\Services\GoogleOtpService;
use App\Services\OtpService;
use App\Services\SecurityRuntimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthPageController extends Controller
{
    private const OTP_LOGIN_SESSION_KEY = 'auth.user_otp_login';
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_DECAY_SECONDS = 300;
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_DECAY_SECONDS = 300;
    private const OTP_SEND_MAX_ATTEMPTS = 3;
    private const OTP_SEND_DECAY_SECONDS = 300;

    protected $otpService;
    protected $deviceApprovalService;
    protected $googleOtpService;
    protected $securityRuntime;

    public function __construct(OtpService $otpService, DeviceApprovalService $deviceApprovalService, GoogleOtpService $googleOtpService, SecurityRuntimeService $securityRuntime)
    {
        $this->otpService = $otpService;
        $this->deviceApprovalService = $deviceApprovalService;
        $this->googleOtpService = $googleOtpService;
        $this->securityRuntime = $securityRuntime;
    }

    public function showLoginForm()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        return view('auth.login', compact('settings'));
    }
    public function login(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);
        $captchaQuestion = $this->securityRuntime->loginCaptchaQuestion($request, 'reseller');
        $devicePreview = $this->deviceApprovalService->preview($request);
        $ip = $devicePreview['ip'];
        $browser = implode(' | ', array_filter([
            $devicePreview['browser'],
            $devicePreview['os'],
            $devicePreview['device_type'],
        ]));

        return view('auth.login', compact('settings', 'ip', 'browser', 'captchaQuestion'));
    }

    public function handleLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'pin' => ['required', 'digits:4'],
        ]);

        if ($response = $this->throttleResponse(
            $request,
            $this->loginThrottleKey($request),
            self::LOGIN_MAX_ATTEMPTS,
            'email',
            'Too many login attempts.'
        )) {
            return $response;
        }

        if (! $this->securityRuntime->validateLoginCaptcha($request, 'reseller', $request->input('captcha'))) {
            return back()->withErrors([
                'captcha' => 'Invalid captcha answer.',
            ])->withInput($request->except(['password', 'pin', 'captcha']));
        }

        $settings = HomepageSetting::first();

        if ($recaptchaError = $this->recaptchaError($request, $settings)) {
            return back()->withErrors([
                'g-recaptcha-response' => $recaptchaError,
            ])->withInput($request->except(['password', 'pin', 'captcha', 'g-recaptcha-response']));
        }

        $remember = $request->boolean('remember');
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->hitThrottle($this->loginThrottleKey($request), self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput($request->except(['password', 'pin']));
        }

        if ($user->is_admin) {
            $this->hitThrottle($this->loginThrottleKey($request), self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'email' => 'Admin accounts cannot login here. Please use admin login.',
            ])->withInput($request->except(['password', 'pin']));
        }

        if (! $user->pin || ! Hash::check($credentials['pin'], $user->pin)) {
            $this->hitThrottle($this->loginThrottleKey($request), self::LOGIN_DECAY_SECONDS);

            return back()->withErrors(['pin' => 'Invalid PIN.'])
                ->withInput($request->except(['password', 'pin']));
        }

        if (! $user->is_active) {
            $this->hitThrottle($this->loginThrottleKey($request), self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->withInput($request->except(['password', 'pin']));
        }

        RateLimiter::clear($this->loginThrottleKey($request));

        if ($settings?->google_otp_enabled && $user->google_otp_enabled) {
            $request->session()->put(self::OTP_LOGIN_SESSION_KEY, [
                'user_id' => $user->id,
                'remember' => $remember,
            ]);

            return redirect()->route('login.otp.show');
        }

        return $this->completeLogin($user, $request, $remember);
    }

    public function showOtpChallenge(Request $request)
    {
        $pendingLogin = $this->pendingLogin($request);

        if (! $pendingLogin) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please login with email, password and PIN first.',
            ]);
        }

        $settings = HomepageSetting::firstOrCreate([]);
        $devicePreview = $this->deviceApprovalService->preview($request);
        $pendingUser = User::query()->find($pendingLogin['user_id']);

        return view('auth.otp-challenge', [
            'settings' => $settings,
            'ip' => $devicePreview['ip'],
            'browser' => implode(' | ', array_filter([
                $devicePreview['browser'],
                $devicePreview['os'],
                $devicePreview['device_type'],
            ])),
            'pendingEmail' => $pendingUser?->email,
            'pageTitle' => 'User OTP Verification',
            'heading' => 'Verify Google OTP',
            'description' => 'Enter the 6 digit Google Authenticator code to complete user login.',
            'formAction' => route('login.otp.verify'),
            'backUrl' => route('login'),
            'backLabel' => 'Back to User Login',
            'submitLabel' => 'Verify & Sign In',
        ]);
    }

    public function verifyOtpChallenge(Request $request)
    {
        $pendingLogin = $this->pendingLogin($request);

        if (! $pendingLogin) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your login session expired. Please login again.',
            ]);
        }

        if ($response = $this->throttleResponse(
            $request,
            $this->otpThrottleKey($request, $pendingLogin),
            self::OTP_MAX_ATTEMPTS,
            'otp',
            'Too many OTP attempts.'
        )) {
            return $response;
        }

        $validated = $request->validate([
            'otp' => ['nullable', 'digits:6'],
        ]);

        if (blank($validated['otp'] ?? null)) {
            return back()->withErrors([
                'otp' => 'Google OTP is required for your account.',
            ]);
        }

        $user = User::query()->find($pendingLogin['user_id']);

        if (! $user || $user->is_admin) {
            $request->session()->forget(self::OTP_LOGIN_SESSION_KEY);
            RateLimiter::clear($this->otpThrottleKey($request, $pendingLogin));

            return redirect()->route('login')->withErrors([
                'email' => 'Unable to continue this login request. Please try again.',
            ]);
        }

        if (! $user->is_active) {
            $request->session()->forget(self::OTP_LOGIN_SESSION_KEY);
            RateLimiter::clear($this->otpThrottleKey($request, $pendingLogin));

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ]);
        }

        $settings = HomepageSetting::first();

        if (! $settings?->google_otp_enabled || ! $user->google_otp_enabled) {
            RateLimiter::clear($this->otpThrottleKey($request, $pendingLogin));

            return $this->completeLogin($user, $request, (bool) ($pendingLogin['remember'] ?? false));
        }

        if (! $this->googleOtpService->verifyCode((string) $user->google_otp_secret, $validated['otp'])) {
            $this->hitThrottle($this->otpThrottleKey($request, $pendingLogin), self::OTP_DECAY_SECONDS);

            return back()->withErrors([
                'otp' => 'Invalid Google Authenticator OTP.',
            ]);
        }

        RateLimiter::clear($this->otpThrottleKey($request, $pendingLogin));

        return $this->completeLogin($user, $request, (bool) ($pendingLogin['remember'] ?? false));
    }

    protected function completeLogin(User $user, Request $request, bool $remember)
    {
        $deviceAccess = $this->deviceApprovalService->authorize($user, $request);
        $request->session()->forget(self::OTP_LOGIN_SESSION_KEY);

        if (! $deviceAccess['allowed']) {
            return redirect()->route('login')
                ->withErrors(['email' => $deviceAccess['message']])
                ->withInput(['email' => $user->email])
                ->cookie($this->deviceApprovalService->makeCookie($deviceAccess['token']));
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->securityRuntime->touchSessionActivity($request);

        return redirect()->intended('dashboard')
            ->cookie($this->deviceApprovalService->makeCookie($deviceAccess['token']));
    }

    protected function pendingLogin(Request $request): ?array
    {
        $pendingLogin = $request->session()->get(self::OTP_LOGIN_SESSION_KEY);

        return is_array($pendingLogin) && filled($pendingLogin['user_id'] ?? null)
            ? $pendingLogin
            : null;
    }

    protected function loginThrottleKey(Request $request): string
    {
        return 'auth:user:login:' . strtolower((string) $request->input('email')) . '|' . $request->ip();
    }

    protected function otpThrottleKey(Request $request, array $pendingLogin): string
    {
        return 'auth:user:otp:' . (string) ($pendingLogin['user_id'] ?? 'guest') . '|' . $request->ip();
    }

    protected function otpSendThrottleKey(Request $request, string $purpose, string $identifier): string
    {
        $normalizedIdentifier = strtolower(trim($identifier));

        if (! str_contains($normalizedIdentifier, '@')) {
            $normalizedIdentifier = preg_replace('/[^0-9]/', '', $normalizedIdentifier) ?? $normalizedIdentifier;
        }

        return 'auth:user:otp-send:' . $purpose . ':' . sha1($normalizedIdentifier) . '|' . $request->ip();
    }

    protected function throttleResponse(Request $request, string $key, int $maxAttempts, string $field, string $label)
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return null;
        }

        $seconds = max(1, RateLimiter::availableIn($key));

        return back()->withErrors([
            $field => $label . ' Please try again in ' . $seconds . ' seconds.',
        ])->withInput($request->except(['password', 'pin', 'captcha', 'g-recaptcha-response', 'otp']));
    }

    protected function throttleJsonResponse(string $key, int $maxAttempts, string $label)
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return null;
        }

        $seconds = max(1, RateLimiter::availableIn($key));

        return response()->json([
            'success' => false,
            'message' => $label . ' Please try again in ' . $seconds . ' seconds.',
            'retry_after' => $seconds,
        ], 429)->header('Retry-After', (string) $seconds);
    }

    protected function hitThrottle(string $key, int $decaySeconds): void
    {
        RateLimiter::hit($key, $decaySeconds);
    }

    public function register()
    {
        $settings = HomepageSetting::first();

        return view('auth.register', compact('settings'));
    }

    public function handleRegister(Request $request)
    {
        $hasReferralColumns = Schema::hasColumn('users', 'referral_code')
            && Schema::hasColumn('users', 'referred_by')
            && Schema::hasColumn('users', 'referral_coin')
            && Schema::hasColumn('homepage_settings', 'referral_reward_coin');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->securityRuntime->passwordRules(),
            'pin' => ['required', 'digits:4'],
            'level' => ['required', 'in:house,dgm,dealer,seller,retailer'],
            'otp' => ['required', 'digits:6'],
        ];

        if ($hasReferralColumns) {
            $rules['referral_code'] = ['nullable', 'string', 'max:50'];
        }

        $validated = $request->validate($rules);

        $settings = HomepageSetting::firstOrCreate([]);
        $referrer = null;

        if ($hasReferralColumns) {
            $referralCode = Str::upper(trim((string) ($validated['referral_code'] ?? '')));

            if ($referralCode !== '') {
                $referrer = User::query()->where('referral_code', $referralCode)->first();

                if (! $referrer) {
                    return back()->withErrors([
                        'referral_code' => 'Invalid referral code.',
                    ])->withInput($request->except(['password', 'password_confirmation', 'pin', 'otp', 'g-recaptcha-response']));
                }
            }
        }

        if ($recaptchaError = $this->recaptchaError($request, $settings)) {
            return back()->withErrors([
                'g-recaptcha-response' => $recaptchaError,
            ])->withInput($request->except(['password', 'password_confirmation', 'pin', 'otp', 'g-recaptcha-response']));
        }

        // Verify OTP
        if (!$this->otpService->verifyOtp($validated['email'], $validated['otp'], 'registration')) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'pin' => Hash::make($validated['pin']),
            'password_changed_at' => now(),
            'pin_changed_at' => now(),
            'is_admin' => false,
            'is_active' => true,
            'level' => $validated['level'],
            'main_bal' => DepositSetting::selfOpeningBalance($validated['level']),
        ];

        if ($hasReferralColumns) {
            $userData['referral_code'] = User::generateReferralCode();
            $userData['referred_by'] = $referrer?->id;
        }

        $user = User::create($userData);

        if ($hasReferralColumns && $referrer) {
            $rewardCoin = (int) ($settings->referral_reward_coin ?? 0);

            if ($rewardCoin > 0) {
                $referrer->increment('referral_coin', $rewardCoin);
            }
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->securityRuntime->touchSessionActivity($request);

        return redirect()->route('dashboard');
    }

    public function sendRegistrationOtp(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email', 'unique:users,email']]);
        $throttleKey = $this->otpSendThrottleKey($request, 'registration-email', $validated['email']);

        if ($response = $this->throttleJsonResponse($throttleKey, self::OTP_SEND_MAX_ATTEMPTS, 'Too many OTP requests.')) {
            return $response;
        }

        $this->hitThrottle($throttleKey, self::OTP_SEND_DECAY_SECONDS);

        if ($this->otpService->sendOtp($validated['email'], 'registration', 'email')) {
            return response()->json(['success' => true, 'message' => 'OTP sent to your email.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send OTP. Please check mail settings.'], 400);
    }

    public function sendRegistrationOtpMobile(Request $request)
    {
        $validated = $request->validate(['mobile' => ['required', 'regex:/^01[0-9]{9}$/', 'unique:users,mobile']]);
        $throttleKey = $this->otpSendThrottleKey($request, 'registration-mobile', $validated['mobile']);

        if ($response = $this->throttleJsonResponse($throttleKey, self::OTP_SEND_MAX_ATTEMPTS, 'Too many OTP requests.')) {
            return $response;
        }

        $this->hitThrottle($throttleKey, self::OTP_SEND_DECAY_SECONDS);

        if ($this->otpService->sendOtp($validated['mobile'], 'registration', 'sms')) {
            return response()->json(['success' => true, 'message' => 'OTP sent to your mobile.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 400);
    }

    public function showForgotPassword()
    {
        $settings = HomepageSetting::first();
        return view('auth.forgot-password', compact('settings'));
    }

    public function sendForgotPasswordOtp(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email', 'exists:users,email']]);
        $throttleKey = $this->otpSendThrottleKey($request, 'forgot-password-email', $validated['email']);

        if ($response = $this->throttleJsonResponse($throttleKey, self::OTP_SEND_MAX_ATTEMPTS, 'Too many OTP requests.')) {
            return $response;
        }

        $this->hitThrottle($throttleKey, self::OTP_SEND_DECAY_SECONDS);

        if ($this->otpService->sendOtp($validated['email'], 'forgot_password', 'email')) {
            return response()->json(['success' => true, 'message' => 'OTP sent to your email.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 400);
    }

    public function sendForgotPasswordOtpMobile(Request $request)
    {
        $validated = $request->validate(['mobile' => ['required', 'regex:/^01[0-9]{9}$/', 'exists:users,mobile']]);
        $throttleKey = $this->otpSendThrottleKey($request, 'forgot-password-mobile', $validated['mobile']);

        if ($response = $this->throttleJsonResponse($throttleKey, self::OTP_SEND_MAX_ATTEMPTS, 'Too many OTP requests.')) {
            return $response;
        }

        $this->hitThrottle($throttleKey, self::OTP_SEND_DECAY_SECONDS);

        if ($this->otpService->sendOtp($validated['mobile'], 'forgot_password', 'sms')) {
            return response()->json(['success' => true, 'message' => 'OTP sent to your mobile.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 400);
    }

    public function handleForgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'digits:6'],
            'password' => $this->securityRuntime->passwordRules(),
        ]);

        if (!$this->otpService->verifyOtp($validated['email'], $validated['otp'], 'forgot_password')) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $user = User::where('email', $validated['email'])->first();
        $user->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ]);

        return redirect()->route('login')->with('success', 'Password reset successfully. Please login.');
    }

    protected function recaptchaEnabled(?HomepageSetting $settings): bool
    {
        return ($settings?->security_recaptcha === 'enable')
            && filled($settings?->recaptcha_site_key)
            && filled($settings?->recaptcha_secret_key);
    }

    protected function recaptchaError(Request $request, ?HomepageSetting $settings): ?string
    {
        if (! $this->recaptchaEnabled($settings)) {
            return null;
        }

        $token = trim((string) $request->input('g-recaptcha-response', ''));

        if ($token === '') {
            return 'Please complete the reCAPTCHA verification.';
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => (string) $settings->recaptcha_secret_key,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (\Throwable $exception) {
            return 'Unable to verify reCAPTCHA right now. Please try again.';
        }

        return $response->successful() && $response->json('success')
            ? null
            : 'reCAPTCHA verification failed. Please try again.';
    }
}
