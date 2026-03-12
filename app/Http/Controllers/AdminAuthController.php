<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
use App\Models\User;
use App\Services\DeviceApprovalService;
use App\Services\GoogleOtpService;
use App\Services\SecurityRuntimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class AdminAuthController extends Controller
{
    private const OTP_LOGIN_SESSION_KEY = 'auth.admin_otp_login';
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_DECAY_SECONDS = 300;
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_DECAY_SECONDS = 300;

    public function __construct(
        protected DeviceApprovalService $deviceApprovalService,
        protected GoogleOtpService $googleOtpService,
        protected SecurityRuntimeService $securityRuntime,
    ) {}

    public function showLogin(Request $request)
    {
        $settings = HomepageSetting::first();
        $captchaQuestion = $this->securityRuntime->loginCaptchaQuestion($request, 'admin');
        $devicePreview = $this->deviceApprovalService->preview($request);
        $ip = $devicePreview['ip'];

        return view('auth.admin-login', compact('settings', 'ip', 'captchaQuestion'));
    }

    public function handleLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'pin' => ['required', 'digits:4'],
        ]);

        if ($response = $this->throttleResponse(
            $request,
            $this->loginThrottleKey($request),
            self::LOGIN_MAX_ATTEMPTS,
            'email',
            'Too many admin login attempts.'
        )) {
            return $response;
        }

        if (! $this->securityRuntime->validateLoginCaptcha($request, 'admin', $request->input('captcha'))) {
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
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            $this->hitThrottle($this->loginThrottleKey($request), self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->withInput($request->except(['password', 'pin']));
        }

        if (! $user || ! $user->is_admin) {
            $this->hitThrottle($this->loginThrottleKey($request), self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'email' => 'This account is not authorized as admin.',
            ])->withInput($request->except(['password', 'pin']));
        }

        if (! $user->is_active) {
            $this->hitThrottle($this->loginThrottleKey($request), self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->withInput($request->except(['password', 'pin']));
        }

        if (! $user->pin || ! Hash::check($validated['pin'], $user->pin)) {
            $this->hitThrottle($this->loginThrottleKey($request), self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'pin' => 'Invalid admin PIN. Please check your 4-digit PIN.',
            ])->withInput($request->except(['password', 'pin']));
        }

        RateLimiter::clear($this->loginThrottleKey($request));

        if ($settings?->google_otp_enabled && $user->google_otp_enabled) {
            $request->session()->put(self::OTP_LOGIN_SESSION_KEY, [
                'user_id' => $user->id,
                'remember' => $remember,
            ]);

            return redirect()->route('admin.login.otp.show');
        }

        return $this->completeLogin($user, $request, $remember);
    }

    public function showOtpChallenge(Request $request)
    {
        $pendingLogin = $this->pendingLogin($request);

        if (! $pendingLogin) {
            return redirect()->route('admin.login')->withErrors([
                'email' => 'Please login with email, password and PIN first.',
            ]);
        }

        $settings = HomepageSetting::firstOrCreate([]);
        $pendingUser = User::query()->find($pendingLogin['user_id']);
        $devicePreview = $this->deviceApprovalService->preview($request);

        return view('auth.otp-challenge', [
            'settings' => $settings,
            'ip' => $devicePreview['ip'],
            'browser' => null,
            'pendingEmail' => $pendingUser?->email,
            'pageTitle' => 'Admin OTP Verification',
            'heading' => 'Verify Admin Google OTP',
            'description' => 'Enter the 6 digit Google Authenticator code to complete admin login.',
            'formAction' => route('admin.login.otp.verify'),
            'backUrl' => route('admin.login'),
            'backLabel' => 'Back to Admin Login',
            'submitLabel' => 'Verify & Open Admin Panel',
        ]);
    }

    public function verifyOtpChallenge(Request $request)
    {
        $pendingLogin = $this->pendingLogin($request);

        if (! $pendingLogin) {
            return redirect()->route('admin.login')->withErrors([
                'email' => 'Your admin login session expired. Please login again.',
            ]);
        }

        if ($response = $this->throttleResponse(
            $request,
            $this->otpThrottleKey($request, $pendingLogin),
            self::OTP_MAX_ATTEMPTS,
            'otp',
            'Too many admin OTP attempts.'
        )) {
            return $response;
        }

        $validated = $request->validate([
            'otp' => ['nullable', 'digits:6'],
        ]);

        if (blank($validated['otp'] ?? null)) {
            return back()->withErrors([
                'otp' => 'Google OTP is required for your admin account.',
            ]);
        }

        $user = User::query()->find($pendingLogin['user_id']);

        if (! $user || ! $user->is_admin) {
            $request->session()->forget(self::OTP_LOGIN_SESSION_KEY);
            RateLimiter::clear($this->otpThrottleKey($request, $pendingLogin));

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Unable to continue this admin login request. Please try again.',
            ]);
        }

        if (! $user->is_active) {
            $request->session()->forget(self::OTP_LOGIN_SESSION_KEY);
            RateLimiter::clear($this->otpThrottleKey($request, $pendingLogin));

            return redirect()->route('admin.login')->withErrors([
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
        $request->session()->forget(self::OTP_LOGIN_SESSION_KEY);
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->securityRuntime->touchSessionActivity($request);

        return redirect()->route('admin.dashboard');
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
        return 'auth:admin:login:' . strtolower((string) $request->input('email')) . '|' . $request->ip();
    }

    protected function otpThrottleKey(Request $request, array $pendingLogin): string
    {
        return 'auth:admin:otp:' . (string) ($pendingLogin['user_id'] ?? 'guest') . '|' . $request->ip();
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

    protected function hitThrottle(string $key, int $decaySeconds): void
    {
        RateLimiter::hit($key, $decaySeconds);
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
