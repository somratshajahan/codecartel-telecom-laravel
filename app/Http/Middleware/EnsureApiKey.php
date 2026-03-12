<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKey
{
    private const API_MAX_ATTEMPTS = 60;
    private const API_DECAY_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->extractApiKey($request);

        if (blank($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key is required.',
            ], 401);
        }

        $user = User::query()
            ->where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key.',
            ], 401);
        }

        $throttleKey = $this->apiThrottleKey($request, $user);

        if ($response = $this->throttleResponse($throttleKey, self::API_MAX_ATTEMPTS)) {
            return $response;
        }

        RateLimiter::hit($throttleKey, self::API_DECAY_SECONDS);

        $clientDomain = $this->resolveClientDomain($request);

        if (! $this->isClientDomainAllowed($request, $user, $clientDomain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized client domain.',
            ], 403);
        }

        if (! $user->hasApprovedApiAccess()) {
            return response()->json([
                'status' => 'error',
                'message' => 'API access is not approved yet.',
            ], 403);
        }

        $request->attributes->set('api_user', $user);
        $request->attributes->set('api_client_domain', $clientDomain);
        $request->setUserResolver(static fn() => $user);

        return $next($request);
    }

    protected function extractApiKey(Request $request): ?string
    {
        foreach ([(string) $request->header('X-API-KEY'), (string) $request->bearerToken(), (string) $request->input('api_key')] as $candidate) {
            $candidate = trim($candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    protected function isClientDomainAllowed(Request $request, User $user, ?string $clientDomain): bool
    {
        $allowedDomains = $user->apiDomains()
            ->pluck('domain')
            ->map(fn($domain) => $this->normalizeDomain((string) $domain))
            ->filter()
            ->unique()
            ->values();

        if ($allowedDomains->isEmpty()) {
            return true;
        }

        if (blank($clientDomain)) {
            return false;
        }

        return $allowedDomains->contains($clientDomain);
    }

    protected function resolveClientDomain(Request $request): ?string
    {
        $candidates = [
            $request->header('X-Client-Domain'),
            $request->input('domain'),
            $request->header('Origin'),
            $request->header('Referer'),
        ];

        foreach ($candidates as $candidate) {
            $domain = $this->normalizeDomain((string) $candidate);

            if ($domain !== null) {
                return $domain;
            }
        }

        return null;
    }

    protected function normalizeDomain(string $value): ?string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/^[a-z]+:\/\//i', '', $value) ?? $value;
        $value = explode('/', $value)[0] ?? $value;
        $value = explode('?', $value)[0] ?? $value;
        $value = explode('#', $value)[0] ?? $value;
        $value = preg_replace('/:\d+$/', '', $value) ?? $value;
        $value = preg_replace('/^www\./', '', $value) ?? $value;

        return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $value)
            ? $value
            : null;
    }

    protected function apiThrottleKey(Request $request, User $user): string
    {
        return 'api:endpoint:' . $user->id . '|' . $request->ip() . '|' . sha1(strtolower((string) $request->path()));
    }

    protected function throttleResponse(string $key, int $maxAttempts): ?Response
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return null;
        }

        $seconds = max(1, RateLimiter::availableIn($key));

        return response()->json([
            'status' => 'error',
            'message' => 'Too many API requests. Please try again in ' . $seconds . ' seconds.',
            'retry_after' => $seconds,
        ], 429)->header('Retry-After', (string) $seconds);
    }
}
