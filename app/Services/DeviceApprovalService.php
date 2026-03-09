<?php

namespace App\Services;

use App\Models\DeviceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Cookie;

class DeviceApprovalService
{
    public const COOKIE_NAME = 'trusted_device_token';

    public function preview(Request $request): array
    {
        $userAgent = (string) $request->header('User-Agent', '');

        return [
            'ip' => $request->ip(),
            'browser' => $this->browserLabel($userAgent),
            'os' => $this->operatingSystem($userAgent),
            'device_type' => ucfirst($this->deviceType($userAgent)),
        ];
    }

    public function authorize(User $user, Request $request): array
    {
        $deviceToken = (string) ($request->cookie(self::COOKIE_NAME) ?: bin2hex(random_bytes(20)));

        if (! Schema::hasTable('device_logs')) {
            return ['allowed' => true, 'token' => $deviceToken];
        }

        $context = $this->deviceContext($request, $deviceToken);
        $identifier = $this->deviceOwnerIdentifier($user);

        $matchingLog = DeviceLog::query()
            ->where('username', $identifier)
            ->where('browser_os', 'like', '%' . $context['device_key'])
            ->latest()
            ->first();

        if ($matchingLog?->status === 'active' && $matchingLog->ip_address === $context['ip_address']) {
            $matchingLog->fill($this->payload($identifier, $context, 'active'))->save();

            return ['allowed' => true, 'token' => $deviceToken];
        }

        if ($matchingLog) {
            $matchingLog->fill($this->payload($identifier, $context, 'deactive'))->save();

            return [
                'allowed' => false,
                'token' => $deviceToken,
                'message' => 'New device detected. Please wait for admin approval before login.',
            ];
        }

        $hasApprovedDevice = DeviceLog::query()
            ->where('username', $identifier)
            ->where('status', 'active')
            ->exists();

        DeviceLog::create($this->payload($identifier, $context, $hasApprovedDevice ? 'deactive' : 'active'));

        return [
            'allowed' => ! $hasApprovedDevice,
            'token' => $deviceToken,
            'message' => $hasApprovedDevice
                ? 'New device detected. Please wait for admin approval before login.'
                : null,
        ];
    }

    public function makeCookie(string $token): Cookie
    {
        return cookie(self::COOKIE_NAME, $token, 60 * 24 * 365, '/', null, false, true, false, 'lax');
    }

    protected function payload(string $identifier, array $context, string $status): array
    {
        return [
            'ip_address' => $context['ip_address'],
            'username' => $identifier,
            'browser_os' => $context['device_label'],
            'two_step_verified' => true,
            'status' => $status,
        ];
    }

    protected function deviceContext(Request $request, string $deviceToken): array
    {
        $userAgent = (string) $request->header('User-Agent', '');
        $deviceKey = 'Key:' . strtoupper(substr(hash('sha256', $deviceToken), 0, 12));

        return [
            'ip_address' => $request->ip(),
            'device_key' => $deviceKey,
            'device_label' => implode(' | ', array_filter([
                ucfirst($this->deviceType($userAgent)),
                $this->browserLabel($userAgent),
                $this->operatingSystem($userAgent),
                $deviceKey,
            ])),
        ];
    }

    protected function deviceOwnerIdentifier(User $user): string
    {
        return (string) ($user->username ?: $user->email ?: $user->id);
    }

    protected function browserLabel(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge ' . $this->version($userAgent, 'Edg'),
            str_contains($userAgent, 'OPR/') => 'Opera ' . $this->version($userAgent, 'OPR'),
            str_contains($userAgent, 'SamsungBrowser/') => 'Samsung Internet ' . $this->version($userAgent, 'SamsungBrowser'),
            str_contains($userAgent, 'Chrome/') => 'Chrome ' . $this->version($userAgent, 'Chrome'),
            str_contains($userAgent, 'Firefox/') => 'Firefox ' . $this->version($userAgent, 'Firefox'),
            str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/') => 'Safari ' . $this->version($userAgent, 'Version'),
            default => 'Unknown Browser',
        };
    }

    protected function operatingSystem(string $userAgent): string
    {
        $normalized = strtolower($userAgent);

        return match (true) {
            str_contains($normalized, 'android') => 'Android',
            str_contains($normalized, 'iphone'), str_contains($normalized, 'ipad'), str_contains($normalized, 'cpu iphone os') => 'iOS',
            str_contains($normalized, 'windows') => 'Windows',
            str_contains($normalized, 'mac os x'), str_contains($normalized, 'macintosh') => 'macOS',
            str_contains($normalized, 'linux') => 'Linux',
            default => 'Unknown OS',
        };
    }

    protected function deviceType(string $userAgent): string
    {
        $normalized = strtolower($userAgent);

        return match (true) {
            str_contains($normalized, 'ipad'), str_contains($normalized, 'tablet') => 'tablet',
            str_contains($normalized, 'mobile'), str_contains($normalized, 'iphone'), str_contains($normalized, 'android') => 'mobile',
            default => 'desktop',
        };
    }

    protected function version(string $userAgent, string $token): string
    {
        preg_match('/' . preg_quote($token, '/') . '\/([0-9\.]+)/', $userAgent, $matches);

        return $matches[1] ?? '';
    }
}
