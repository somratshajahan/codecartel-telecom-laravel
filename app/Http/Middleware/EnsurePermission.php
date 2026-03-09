<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (! $user) {
            $loginRoute = $request->is('admin') || $request->is('admin/*')
                ? route('admin.login')
                : route('login');

            return redirect()->guest($loginRoute);
        }

        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        $redirectRoute = $user->is_admin ? 'admin.dashboard' : 'dashboard';

        return redirect()->route($redirectRoute)
            ->with('error', 'You do not have permission to access this page.');
    }
}