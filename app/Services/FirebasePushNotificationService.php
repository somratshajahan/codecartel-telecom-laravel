<?php

namespace App\Services;

use App\Models\HomepageSetting;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FirebasePushNotificationService
{
    public function hasWebPushConfig(): bool
    {
        $settings = HomepageSetting::first();

        return filled($settings?->firebase_api_key)
            && filled($settings?->firebase_project_id)
            && filled($settings?->firebase_messaging_sender_id)
            && filled($settings?->firebase_app_id)
            && filled($settings?->firebase_vapid_key);
    }

    public function canSendNotifications(): bool
    {
        $settings = HomepageSetting::first();

        return $this->hasWebPushConfig() && filled($settings?->firebase_service_account_json);
    }

    public function webConfig(?HomepageSetting $settings = null): array
    {
        $settings ??= HomepageSetting::first();

        return array_filter([
            'apiKey' => $settings?->firebase_api_key,
            'authDomain' => $settings?->firebase_auth_domain,
            'projectId' => $settings?->firebase_project_id,
            'storageBucket' => $settings?->firebase_storage_bucket,
            'messagingSenderId' => $settings?->firebase_messaging_sender_id,
            'appId' => $settings?->firebase_app_id,
        ]);
    }

    public function sendToUser(?User $user, string $title, string $body, ?string $link = null): void
    {
        if (! $user || ! $this->hasUserFcmTokenColumn() || blank($user->fcm_token)) {
            return;
        }

        $this->sendToUsers([$user], $title, $body, $link);
    }

    public function sendToAdmins(string $title, string $body, ?string $link = null): void
    {
        if (! $this->hasUserFcmTokenColumn()) {
            return;
        }

        $this->sendToUsers(
            User::where('is_admin', true)->whereNotNull('fcm_token')->get(),
            $title,
            $body,
            $link,
        );
    }

    public function sendToAllUsers(string $title, string $body, ?string $link = null): void
    {
        if (! $this->hasUserFcmTokenColumn()) {
            return;
        }

        $this->sendToUsers(
            User::where('is_admin', false)->whereNotNull('fcm_token')->get(),
            $title,
            $body,
            $link,
        );
    }

    public function sendToUsers(iterable $users, string $title, string $body, ?string $link = null): void
    {
        if (! $this->hasUserFcmTokenColumn()) {
            return;
        }

        $settings = HomepageSetting::first();

        if (! $settings || ! $this->canSendNotifications()) {
            return;
        }

        $targets = collect($users)
            ->filter(fn($user) => $user instanceof User && filled($user->fcm_token))
            ->unique('id')
            ->values();

        if ($targets->isEmpty()) {
            return;
        }

        $accessToken = $this->fetchAccessToken($settings);
        $projectId = $this->resolveProjectId($settings);

        if (blank($accessToken) || blank($projectId)) {
            return;
        }

        foreach ($targets as $user) {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => $this->buildMessage($user->fcm_token, $title, $body, $link, $settings),
                ]);

            if (! $response->successful()) {
                $this->handleSendFailure($user, $response);
            }
        }
    }

    protected function buildMessage(string $token, string $title, string $body, ?string $link, HomepageSetting $settings): array
    {
        $notificationIcon = $settings->company_logo_url
            ? asset($settings->company_logo_url)
            : ($settings->favicon_path ? asset($settings->favicon_path) : null);

        $targetLink = $this->normalizeLink($link);

        return [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => array_filter([
                'title' => $title,
                'body' => $body,
                'link' => $targetLink,
            ]),
            'webpush' => [
                'headers' => [
                    'Urgency' => 'high',
                ],
                'notification' => array_filter([
                    'title' => $title,
                    'body' => $body,
                    'icon' => $notificationIcon,
                ]),
                'fcm_options' => array_filter([
                    'link' => $targetLink,
                ]),
            ],
        ];
    }

    protected function fetchAccessToken(HomepageSetting $settings): ?string
    {
        $serviceAccount = $this->serviceAccountCredentials($settings);

        if (! $serviceAccount) {
            return null;
        }

        $jwt = $this->makeJwt($serviceAccount);

        if (! $jwt) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            Log::warning('Unable to fetch Firebase access token.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('access_token');
    }

    protected function resolveProjectId(HomepageSetting $settings): ?string
    {
        $serviceAccount = $this->serviceAccountCredentials($settings);

        return $settings->firebase_project_id ?: ($serviceAccount['project_id'] ?? null);
    }

    protected function serviceAccountCredentials(HomepageSetting $settings): ?array
    {
        $json = trim((string) $settings->firebase_service_account_json);

        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            Log::warning('Firebase service account JSON is invalid or incomplete.');

            return null;
        }

        return $decoded;
    }

    protected function makeJwt(array $serviceAccount): ?string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + 3600;

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $claimSet = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ]));

        $signatureInput = $header . '.' . $claimSet;
        $signature = '';

        if (! openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256)) {
            Log::warning('Unable to sign Firebase JWT.');

            return null;
        }

        return $signatureInput . '.' . $this->base64UrlEncode($signature);
    }

    protected function handleSendFailure(User $user, Response $response): void
    {
        $body = $response->body();

        Log::warning('Firebase push send failed.', [
            'user_id' => $user->id,
            'status' => $response->status(),
            'body' => $body,
        ]);

        if ((str_contains($body, 'UNREGISTERED') || str_contains($body, 'registration-token-not-registered')) && $this->hasUserFcmTokenColumn()) {
            $updates = ['fcm_token' => null];

            if ($this->hasUserFcmTokenUpdatedAtColumn()) {
                $updates['fcm_token_updated_at'] = now();
            }

            $user->forceFill($updates)->save();
        }
    }

    protected function hasUserFcmTokenColumn(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'fcm_token');
    }

    protected function hasUserFcmTokenUpdatedAtColumn(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'fcm_token_updated_at');
    }

    protected function normalizeLink(?string $link): ?string
    {
        if (blank($link)) {
            return null;
        }

        if (str_starts_with($link, 'http://') || str_starts_with($link, 'https://')) {
            return $link;
        }

        return url($link);
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
