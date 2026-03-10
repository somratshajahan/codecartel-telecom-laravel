<?php

namespace App\Services;

use App\Models\Branding;
use Illuminate\Support\Facades\Http;

class SslCommerzService
{
    public function isConfigured(?Branding $branding = null): bool
    {
        $branding ??= Branding::first();

        return filled($branding?->sslcommerz_store_id)
            && filled($branding?->sslcommerz_store_password);
    }

    public function initiateSession(Branding $branding, array $payload): array
    {
        $response = Http::asForm()
            ->acceptJson()
            ->post($this->gatewayUrl($branding), array_merge($this->credentials($branding), $payload));

        $data = $response->json();

        if (! $response->successful() || ! is_array($data)) {
            return [
                'ok' => false,
                'data' => is_array($data) ? $data : [],
                'message' => 'Unable to initiate SSLCommerz payment right now.',
            ];
        }

        $status = strtoupper(trim((string) ($data['status'] ?? '')));
        $gatewayUrl = trim((string) ($data['GatewayPageURL'] ?? $data['redirectGatewayURL'] ?? ''));

        if ($status !== 'SUCCESS' || $gatewayUrl === '') {
            return [
                'ok' => false,
                'data' => $data,
                'message' => trim((string) ($data['failedreason'] ?? $data['failedReason'] ?? 'SSLCommerz session creation failed.')),
            ];
        }

        return [
            'ok' => true,
            'data' => $data,
            'message' => 'SSLCommerz session created successfully.',
        ];
    }

    public function validateTransaction(Branding $branding, array $payload): array
    {
        $query = array_merge(
            $this->credentials($branding),
            ['format' => 'json'],
            [
                'val_id' => $payload['val_id'] ?? null,
                'tran_id' => $payload['tran_id'] ?? null,
                'amount' => $payload['amount'] ?? null,
                'currency' => $payload['currency'] ?? 'BDT',
            ],
        );

        $query = array_filter($query, fn($value) => $value !== null && $value !== '');

        $response = Http::acceptJson()->get($this->validationUrl($branding), $query);

        $data = $response->json();

        if (! $response->successful() || ! is_array($data)) {
            return [
                'ok' => false,
                'data' => is_array($data) ? $data : [],
                'message' => 'Unable to validate SSLCommerz transaction right now.',
            ];
        }

        $status = strtoupper(trim((string) ($data['status'] ?? '')));

        if (! in_array($status, ['VALID', 'VALIDATED'], true)) {
            return [
                'ok' => false,
                'data' => $data,
                'message' => trim((string) ($data['status_details'] ?? $data['error'] ?? 'SSLCommerz transaction validation failed.')),
            ];
        }

        return [
            'ok' => true,
            'data' => $data,
            'message' => 'SSLCommerz transaction validated successfully.',
        ];
    }

    protected function credentials(Branding $branding): array
    {
        return [
            'store_id' => trim((string) $branding->sslcommerz_store_id),
            'store_passwd' => trim((string) $branding->sslcommerz_store_password),
        ];
    }

    protected function gatewayUrl(Branding $branding): string
    {
        return $this->isLive($branding)
            ? 'https://securepay.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
    }

    protected function validationUrl(Branding $branding): string
    {
        return $this->isLive($branding)
            ? 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php'
            : 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';
    }

    protected function isLive(Branding $branding): bool
    {
        return strtolower(trim((string) $branding->sslcommerz_mode)) === 'live';
    }
}
