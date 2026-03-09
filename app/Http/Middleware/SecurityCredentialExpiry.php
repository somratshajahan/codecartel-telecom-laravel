<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SecurityRuntimeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityCredentialExpiry
{
    public function __construct(private SecurityRuntimeService $securityRuntime)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $message = $this->securityRuntime->expiredCredentialMessage($user);

        if ($message === null || $this->allowsCredentialRemediation($request, $user)) {
            return $next($request);
        }

        if ($user->is_admin) {
            return redirect()->route('admin.change.credentials')->with('error', $message);
        }

        return redirect()->route('user.profile')->withErrors([
            'credential_expiry' => $message,
        ]);
    }

    private function allowsCredentialRemediation(Request $request, User $user): bool
    {
        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            return false;
        }

        $allowedRoutes = $user->is_admin
            ? ['admin.change.credentials', 'admin.update.password', 'admin.update.pin', 'logout']
            : ['user.profile', 'user.profile.password', 'user.profile.pin', 'logout'];

        return in_array($routeName, $allowedRoutes, true);
    }
}