<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthPageController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
   
    public function showLoginForm()
    {
        $settings = HomepageSetting::first();
        return view('auth.login', compact('settings'));
    }
    public function login(Request $request) 
    {
        $settings = HomepageSetting::first();

        // 1. IP Address get kora
        $ip = $request->ip();

        // 2. Browser logic
        $userAgent = $request->header('User-Agent');
        $browser = "Unknown Browser";

        if (strpos($userAgent, 'Chrome') !== false) {
            preg_match('/Chrome\/([0-9\.]+)/', $userAgent, $matches);
            $browser = "Chrome " . ($matches[1] ?? '');
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = "Firefox";
        }

        // View-te data pathano holo
        return view('auth.login', compact('settings', 'ip', 'browser'));
    }

    public function handleLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'pin' => ['required', 'digits:4'],
        ]);

        $remember = $request->boolean('remember');

       if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $remember)) {
        /** @var User $user */
        $user = Auth::user();

           // Check if user is admin
           if ($user->is_admin) {
               Auth::logout();
               return back()->withErrors([
                   'email' => 'Admin accounts cannot login here. Please use admin login.',
               ]);
           }

           if (! $user->pin || ! Hash::check($credentials['pin'], $user->pin)) {
            Auth::logout();
            return back()->withErrors(['pin' => 'Invalid PIN.']);
        }

            // if user is inactive, log them out immediately
            if (! $user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact support.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
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
            'password' => ['required', 'min:6', 'confirmed'],
            'pin' => ['required', 'digits:4'],
            'level' => ['required', 'in:house,dgm,dealer,seller,retailer'],
            'otp' => ['required', 'digits:6'],
        ]);

        // Verify OTP
        if (!$this->otpService->verifyOtp($validated['email'], $validated['otp'], 'registration')) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

       $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']), 
        'pin' => Hash::make($validated['pin']),          
        'is_admin' => false,
        'is_active' => true,
        'level' => $validated['level'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

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
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        if (!$this->otpService->verifyOtp($validated['email'], $validated['otp'], 'forgot_password')) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $user = User::where('email', $validated['email'])->first();
        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('login')->with('success', 'Password reset successfully. Please login.');
    }
}

