<?php

namespace App\Http\Middleware;

use App\Services\SecurityRuntimeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecuritySessionTimeout
{
    public function __construct(private SecurityRuntimeService $securityRuntime)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutMinutes = $this->securityRuntime->sessionTimeoutMinutes();

        if ($timeoutMinutes > 0) {
            $lastActivity = $this->securityRuntime->sessionLastActivity($request);

            if ($lastActivity !== null && $lastActivity < now()->subMinutes($timeoutMinutes)->timestamp) {
                $isAdmin = (bool) optional(Auth::user())->is_admin;

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route($isAdmin ? 'admin.login' : 'login')
                    ->withErrors([
                        'email' => 'Your session expired due to inactivity. Please login again.',
                    ]);
            }
        }

        $this->securityRuntime->touchSessionActivity($request);

        return $next($request);
    }
}