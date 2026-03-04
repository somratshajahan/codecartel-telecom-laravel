<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        $settings = HomepageSetting::first();

        return view('auth.admin-login', compact('settings'));
    }

    public function handleLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'pin' => ['required', 'digits:4'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $remember)) {
            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->withInput();
        }

        $user = Auth::user();

        if (! $user || ! $user->is_admin) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'This account is not authorized as admin.',
            ])->withInput();
        }

        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->withInput();
        }

        if (! $user->pin || ! Hash::check($validated['pin'], $user->pin)) {
            Auth::logout();
            return back()->withErrors([
                'pin' => 'Invalid admin PIN. Please check your 4-digit PIN.',
            ])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}

