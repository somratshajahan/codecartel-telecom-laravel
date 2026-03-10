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

class AuthPageController extends Controller
{
    private const OTP_LOGIN_SESSION_KEY = 'auth.user_otp_login';

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
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput($request->except(['password', 'pin']));
        }

        if ($user->is_admin) {
            return back()->withErrors([
                'email' => 'Admin accounts cannot login here. Please use admin login.',
            ])->withInput($request->except(['password', 'pin']));
        }

        if (! $user->pin || ! Hash::check($credentials['pin'], $user->pin)) {
            return back()->withErrors(['pin' => 'Invalid PIN.'])
                ->withInput($request->except(['password', 'pin']));
        }

        if (! $user->is_active) {
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->withInput($request->except(['password', 'pin']));
        }

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

            return redirect()->route('login')->withErrors([
                'email' => 'Unable to continue this login request. Please try again.',
            ]);
        }

        if (! $user->is_active) {
            $request->session()->forget(self::OTP_LOGIN_SESSION_KEY);

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ]);
        }

        $settings = HomepageSetting::first();

        if (! $settings?->google_otp_enabled || ! $user->google_otp_enabled) {
            return $this->completeLogin($user, $request, (bool) ($pendingLogin['remember'] ?? false));
        }

        if (! $this->googleOtpService->verifyCode((string) $user->google_otp_secret, $validated['otp'])) {
            return back()->withErrors([
                'otp' => 'Invalid Google Authenticator OTP.',
            ]);
        }

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

    public function register()
    {
        $settings = HomepageSetting::first();

        return view('auth.register', compact('settings'));
    }

    public function handleRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->securityRuntime->passwordRules(),
            'pin' => ['required', 'digits:4'],
            'level' => ['required', 'in:house,dgm,dealer,seller,retailer'],
            'otp' => ['required', 'digits:6'],
        ]);

        if ($recaptchaError = $this->recaptchaError($request, HomepageSetting::first())) {
            return back()->withErrors([
                'g-recaptcha-response' => $recaptchaError,
            ])->withInput($request->except(['password', 'password_confirmation', 'pin', 'otp', 'g-recaptcha-response']));
        }

        // Verify OTP
        if (!$this->otpService->verifyOtp($validated['email'], $validated['otp'], 'registration')) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $user = User::create([
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
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->securityRuntime->touchSessionActivity($request);

        return redirect()->route('dashboard');
    }

    public function sendRegistrationOtp(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'unique:users,email']]);

        if ($this->otpService->sendOtp($request->email, 'registration', 'email')) {
            return response()->json(['success' => true, 'message' => 'OTP sent to your email.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send OTP. Please check mail settings.'], 400);
    }

    public function sendRegistrationOtpMobile(Request $request)
    {
        $request->validate(['mobile' => ['required', 'regex:/^01[0-9]{9}$/', 'unique:users,mobile']]);

        if ($this->otpService->sendOtp($request->mobile, 'registration', 'sms')) {
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
        $request->validate(['email' => ['required', 'email', 'exists:users,email']]);

        if ($this->otpService->sendOtp($request->email, 'forgot_password', 'email')) {
            return response()->json(['success' => true, 'message' => 'OTP sent to your email.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 400);
    }

    public function sendForgotPasswordOtpMobile(Request $request)
    {
        $request->validate(['mobile' => ['required', 'regex:/^01[0-9]{9}$/', 'exists:users,mobile']]);

        if ($this->otpService->sendOtp($request->mobile, 'forgot_password', 'sms')) {
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
