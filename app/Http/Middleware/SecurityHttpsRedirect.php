<?php

namespace App\Http\Middleware;

use App\Services\SecurityRuntimeService;
use Closure;
use Illuminate\Http\Request;

class SecurityHttpsRedirect
{
    public function __construct(private SecurityRuntimeService $securityRuntime)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (
            ! app()->environment(['local', 'testing'])
            && $this->securityRuntime->isHttpsRedirectEnabled()
            && ! $request->secure()
        ) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}