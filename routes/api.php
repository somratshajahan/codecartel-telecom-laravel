<?php

use App\Models\Branding;
use App\Models\Api;
use App\Models\ApiRoute;
use App\Models\DrivePackage;
use App\Models\DriveRequest;
use App\Models\FlexiRequest;
use App\Models\ManualPaymentRequest;
use App\Models\RegularPackage;
use App\Models\RegularRequest;
use App\Models\User;
use App\Services\FirebasePushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

$normalizeFlexiOperatorName = static function (?string $value): string {
    return strtolower(preg_replace('/[^a-z]/i', '', (string) $value));
};

$flexiOperatorPrefixes = [
    'grameenphone' => ['017', '013'],
    'robi' => ['018'],
    'airtel' => ['016'],
    'banglalink' => ['019', '014'],
    'teletalk' => ['015'],
];

$flexiOperatorNames = [
    'grameenphone' => 'Grameenphone',
    'robi' => 'Robi',
    'airtel' => 'Airtel',
    'banglalink' => 'Banglalink',
    'teletalk' => 'Teletalk',
];

$resolveFlexiOperatorKeyFromMobile = static function (string $mobile) use ($flexiOperatorPrefixes): ?string {
    $prefix = substr(preg_replace('/[^0-9]/', '', $mobile), 0, 3);

    foreach ($flexiOperatorPrefixes as $operatorKey => $prefixes) {
        if (in_array($prefix, $prefixes, true)) {
            return $operatorKey;
        }
    }

    return null;
};

$internetOperatorPrefixes = [
    'grameenphone' => ['017', '013'],
    'gp' => ['017', '013'],
    'robi' => ['018'],
    'airtel' => ['016'],
    'banglalink' => ['019', '014'],
    'bl' => ['019', '014'],
    'teletalk' => ['015'],
    'tt' => ['015'],
];

Route::prefix('v1')->middleware('auth.api_key')->group(function () use ($internetOperatorPrefixes, $normalizeFlexiOperatorName, $flexiOperatorNames, $resolveFlexiOperatorKeyFromMobile) {
    $routeProductCodes = [
        'grameenphone' => 'Gp',
        'gp' => 'Gp',
        'skitto' => 'SK',
        'sk' => 'SK',
        'robi' => 'RB',
        'airtel' => 'AT',
        'teletalk' => 'TT',
        'tt' => 'TT',
        'banglalink' => 'BL',
        'bl' => 'BL',
    ];

    $defaultBalanceTypeForService = static function (string $service): string {
        return match ($service) {
            'drive' => 'drive_bal',
            'bkash', 'nagad', 'rocket', 'upay' => 'bank_bal',
            default => 'main_bal',
        };
    };

    $resolveRouteProductCode = static function (?string $operator) use ($routeProductCodes): ?string {
        if (! filled($operator)) {
            return null;
        }

        $normalized = strtolower(preg_replace('/[^a-z]/i', '', (string) $operator));

        return $routeProductCodes[$normalized] ?? null;
    };

    $findMatchingApiRoute = static function (string $service, ?string $code = null, ?string $prefix = null) {
        if (! Schema::hasTable('api_routes') || ! Schema::hasTable('apis')) {
            return null;
        }

        $candidateRoutes = ApiRoute::query()
            ->with('apiConnection')
            ->where('status', 'active')
            ->orderBy('priority')
            ->orderByDesc('id')
            ->get();

        $requestPrefix = trim((string) ($prefix ?? ''));

        return $candidateRoutes->first(function (ApiRoute $route) use ($service, $code, $requestPrefix) {
            if (! in_array($route->service, [$service, 'all'], true)) {
                return false;
            }

            if ($code !== null && $code !== '') {
                if (! in_array($route->code, [$code, 'all'], true)) {
                    return false;
                }
            } elseif ($route->code !== 'all') {
                return false;
            }

            $routePrefix = trim((string) ($route->prefix ?? ''));

            if ($routePrefix !== '' && ($requestPrefix === '' || ! str_starts_with($requestPrefix, $routePrefix))) {
                return false;
            }

            return true;
        });
    };

    $resolveSameBillingBalanceType = static function (string $service, ?string $code = null, ?string $prefix = null) use ($defaultBalanceTypeForService, $findMatchingApiRoute) {
        $matchedRoute = $findMatchingApiRoute($service, $code, $prefix);

        if (! $matchedRoute || ! $matchedRoute->apiConnection) {
            return null;
        }

        if (strtolower((string) $matchedRoute->apiConnection->provider) !== 'same billing') {
            return null;
        }

        return $defaultBalanceTypeForService($service);
    };

    $resolveForwardableMatchedRoute = static function ($matchedRoute) {
        if (! $matchedRoute instanceof ApiRoute) {
            return null;
        }

        if (strtolower((string) ($matchedRoute->module_type ?? '')) !== 'api') {
            return null;
        }

        $connection = $matchedRoute->apiConnection;

        if (! $connection || strtolower((string) ($connection->status ?? '')) !== 'active') {
            return null;
        }

        if ($connection->approvalStatus() !== 1) {
            return null;
        }

        if (strtolower((string) $connection->provider) === 'same billing') {
            return null;
        }

        return $matchedRoute;
    };

    $resolveForwardableApiRoute = static function (string $service, ?string $code = null, ?string $prefix = null) use ($findMatchingApiRoute, $resolveForwardableMatchedRoute) {
        return $resolveForwardableMatchedRoute($findMatchingApiRoute($service, $code, $prefix));
    };

    $withAvailableColumns = static function (string $table, array $attributes, array $optionalValues): array {
        if (! Schema::hasTable($table)) {
            return $attributes;
        }

        foreach ($optionalValues as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $attributes[$column] = $value;
            }
        }

        return $attributes;
    };

    $incomingRoutedAttributes = static function (Request $request, string $requestType): array {
        $sourceRequestId = trim((string) $request->input('source_request_id', ''));
        $sourceApiKey = trim((string) $request->input('source_api_key', ''));
        $sourceCallbackUrl = trim((string) $request->input('source_callback_url', ''));

        if ($sourceRequestId === '' || $sourceApiKey === '' || $sourceCallbackUrl === '') {
            return [];
        }

        $sourceClientDomain = trim((string) ($request->input('source_client_domain') ?: $request->attributes->get('api_client_domain')));

        return [
            'is_routed' => true,
            'source_request_id' => $sourceRequestId,
            'source_request_type' => $requestType,
            'source_api_key' => $sourceApiKey,
            'source_callback_url' => $sourceCallbackUrl,
            'source_client_domain' => $sourceClientDomain !== '' ? $sourceClientDomain : null,
        ];
    };

    $normalizeApiUrl = static function (string $url): string {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    };

    $resolveServiceRequestUrls = static function (Api $connection, string $service) use ($normalizeApiUrl): array {
        $baseUrl = $normalizeApiUrl((string) $connection->api_url);

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return [];
        }

        $urls = [];
        $path = (string) parse_url($baseUrl, PHP_URL_PATH);

        if ($path !== '' && preg_match('#/(balance|auth-check|recharge|drive|internet)$#i', $path)) {
            $urls[] = preg_replace('#/(balance|auth-check|recharge|drive|internet)$#i', '/' . $service, $baseUrl) ?: $baseUrl;
        }

        $trimmedBaseUrl = rtrim($baseUrl, '/');
        $urls[] = $trimmedBaseUrl . '/' . $service;

        $scheme = (string) parse_url($baseUrl, PHP_URL_SCHEME);
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);
        $port = parse_url($baseUrl, PHP_URL_PORT);

        if ($scheme !== '' && $host !== '') {
            $origin = $scheme . '://' . $host . ($port ? ':' . $port : '');
            $urls[] = rtrim($origin, '/') . '/api/v1/' . $service;
        }

        return array_values(array_unique(array_filter($urls, fn($url) => filter_var($url, FILTER_VALIDATE_URL))));
    };

    $forwardRoutedRequest = static function (ApiRoute $matchedRoute, string $service, array $payload, ?string $clientDomain = null) use ($resolveServiceRequestUrls) {
        $connection = $matchedRoute->apiConnection;

        if (! $connection) {
            return ['ok' => false, 'message' => 'Provider API connection is missing.'];
        }

        $urls = $resolveServiceRequestUrls($connection, $service);

        if ($urls === []) {
            return ['ok' => false, 'message' => 'Provider API URL is invalid.'];
        }

        $headers = ['X-API-KEY' => $connection->api_key, 'Accept' => 'application/json'];

        if (filled($clientDomain)) {
            $headers['X-Client-Domain'] = $clientDomain;
        }

        $lastMessage = 'Provider request forwarding failed.';

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders($headers)
                    ->post($url, $payload);
            } catch (\Throwable $exception) {
                $lastMessage = 'Provider request forwarding failed: ' . $exception->getMessage();
                continue;
            }

            if (! $response->successful()) {
                $lastMessage = 'Provider returned HTTP ' . $response->status() . '.';
                continue;
            }

            $json = $response->json();

            if (($json['status'] ?? null) !== 'success' || ! filled($json['request_id'] ?? null)) {
                $lastMessage = (string) ($json['message'] ?? 'Provider did not accept the routed request.');
                continue;
            }

            return [
                'ok' => true,
                'request_id' => (string) $json['request_id'],
                'trx_id' => $json['trx_id'] ?? null,
                'message' => (string) ($json['message'] ?? 'Request Received'),
            ];
        }

        return ['ok' => false, 'message' => $lastMessage];
    };

    $routedRequestContext = static function (string $requestType): ?array {
        return match ($requestType) {
            'recharge' => [
                'model' => FlexiRequest::class,
                'table' => 'flexi_requests',
                'default_balance_type' => 'main_bal',
            ],
            'drive' => [
                'model' => DriveRequest::class,
                'table' => 'drive_requests',
                'default_balance_type' => 'drive_bal',
            ],
            'internet' => [
                'model' => RegularRequest::class,
                'table' => 'regular_requests',
                'default_balance_type' => 'main_bal',
            ],
            default => null,
        };
    };

    $ensureApiServiceEnabled = static function (Request $request, string $service) {
        /** @var User $user */
        $user = $request->attributes->get('api_user');
        $serviceLabel = User::apiServiceOptions()[$service] ?? ucfirst($service);

        if (! $user->hasEnabledApiService($service)) {
            return response()->json([
                'status' => 'error',
                'message' => $serviceLabel . ' API service is disabled.',
            ], 403);
        }

        return null;
    };

    $submitManualPaymentRequest = static function (Request $request, string $serviceKey, string $methodLabel) use ($ensureApiServiceEnabled) {
        if ($blockedResponse = $ensureApiServiceEnabled($request, $serviceKey)) {
            return $blockedResponse;
        }

        if (! Schema::hasTable('manual_payment_requests')) {
            return response()->json(['status' => 'error', 'message' => 'Manual payment request table is not ready.'], 503);
        }

        $branding = Branding::query()->first();
        $availableMethods = collect([
            'Bkash' => $branding->bkash ?? null,
            'Rocket' => $branding->rocket ?? null,
            'Nagad' => $branding->nagad ?? null,
            'Upay' => $branding->upay ?? null,
        ])->filter(fn($number) => filled($number));

        if (! $availableMethods->has($methodLabel)) {
            return response()->json([
                'status' => 'error',
                'message' => $methodLabel . ' manual payment method is not available right now.',
            ], 503);
        }

        $validated = $request->validate([
            'sender_number' => ['required', 'regex:/^01[0-9]{9}$/'],
            'transaction_id' => ['required', 'string', 'max:255', 'unique:manual_payment_requests,transaction_id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->attributes->get('api_user');
        $manualPaymentRequest = ManualPaymentRequest::create([
            'user_id' => $user->id,
            'method' => $methodLabel,
            'sender_number' => trim((string) $validated['sender_number']),
            'transaction_id' => trim((string) $validated['transaction_id']),
            'amount' => $validated['amount'],
            'note' => filled($validated['note'] ?? null) ? trim((string) $validated['note']) : null,
            'status' => 'pending',
        ]);

        app(FirebasePushNotificationService::class)->sendToAdmins(
            'New API Manual Payment Request',
            $user->name . ' submitted an API ' . $methodLabel . ' add-balance request.',
            route('admin.pending.drive.requests'),
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Request Received',
            'method' => $methodLabel,
            'trx_id' => $manualPaymentRequest->transaction_id,
            'request_id' => $manualPaymentRequest->id,
        ], 201);
    };

    Route::post('/auth-check', function (Request $request) {
        /** @var User $user */
        $user = $request->attributes->get('api_user');

        return response()->json([
            'status' => 'success',
            'message' => 'Authenticated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    });

    Route::post('/balance', function (Request $request) {
        /** @var User $user */
        $user = $request->attributes->get('api_user');

        return response()->json([
            'status' => 'success',
            'message' => 'Balance fetched successfully.',
            'balances' => [
                'main_balance' => (float) ($user->main_bal ?? 0),
                'drive_balance' => (float) ($user->drive_bal ?? 0),
                'bank_balance' => (float) ($user->bank_bal ?? 0),
            ],
        ]);
    });

    Route::post('/routed-settlement', function (Request $request) use ($routedRequestContext, $withAvailableColumns) {
        $validated = $request->validate([
            'source_request_id' => ['required', 'integer'],
            'request_type' => ['required', 'in:recharge,drive,internet'],
            'status' => ['required', 'in:approved,rejected,cancelled'],
            'remote_request_id' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'trnx_id' => ['nullable', 'string', 'max:255'],
        ]);

        $context = $routedRequestContext($validated['request_type']);

        if (! $context || ! Schema::hasTable($context['table'])) {
            return response()->json(['status' => 'error', 'message' => 'Routed request context is unavailable.'], 404);
        }

        $modelClass = $context['model'];
        $requestModel = $modelClass::query()->find($validated['source_request_id']);

        if (! $requestModel || ! (bool) ($requestModel->is_routed ?? false)) {
            return response()->json(['status' => 'error', 'message' => 'Routed source request not found.'], 404);
        }

        if ($requestModel->status !== 'pending') {
            return response()->json([
                'status' => 'success',
                'message' => 'Request already settled.',
                'settled_status' => $requestModel->status,
            ]);
        }

        $settledStatus = $validated['status'];

        DB::transaction(function () use ($context, $modelClass, $validated, $withAvailableColumns, &$settledStatus) {
            $lockedRequest = $modelClass::query()->lockForUpdate()->find($validated['source_request_id']);

            if (! $lockedRequest || $lockedRequest->status !== 'pending') {
                $settledStatus = $lockedRequest?->status ?? $settledStatus;
                return;
            }

            $user = User::query()->lockForUpdate()->find($lockedRequest->user_id);
            $description = filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null;
            $remoteRequestId = filled($validated['remote_request_id'] ?? null) ? trim((string) $validated['remote_request_id']) : null;
            $baseUpdate = $withAvailableColumns($context['table'], [], [
                'remote_request_id' => $remoteRequestId,
                'settled_at' => now(),
            ]);

            if ($validated['status'] === 'approved') {
                $balanceType = $lockedRequest->balance_type ?: $context['default_balance_type'];

                if (! in_array($balanceType, ['main_bal', 'drive_bal', 'bank_bal'], true)) {
                    $balanceType = $context['default_balance_type'];
                }

                $user->{$balanceType} = (float) ($user->{$balanceType} ?? 0) - (float) $lockedRequest->amount;
                $user->save();

                $update = array_merge(['status' => 'approved'], $baseUpdate, $withAvailableColumns($context['table'], [], [
                    'charged_at' => now(),
                ]));

                if ($validated['request_type'] === 'recharge' && filled($validated['trnx_id'] ?? null)) {
                    $update['trnx_id'] = trim((string) $validated['trnx_id']);
                }

                if ($validated['request_type'] === 'internet') {
                    $update['description'] = $description ?: 'Success';
                }

                $lockedRequest->update($update);

                if ($validated['request_type'] === 'drive') {
                    DB::table('drive_history')->insert([
                        'user_id' => $lockedRequest->user_id,
                        'package_id' => $lockedRequest->package_id,
                        'operator' => $lockedRequest->operator,
                        'mobile' => $lockedRequest->mobile,
                        'amount' => $lockedRequest->amount,
                        'status' => 'success',
                        'description' => $description ?: 'Routed provider success',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $settledStatus = 'approved';

                return;
            }

            if ($validated['status'] === 'cancelled') {
                $update = array_merge(['status' => 'cancelled'], $baseUpdate);

                if ($validated['request_type'] === 'internet') {
                    $update['description'] = $description ?: 'Request cancelled by provider';
                }

                $lockedRequest->update($update);

                if ($validated['request_type'] === 'drive') {
                    DB::table('drive_history')->insert([
                        'user_id' => $lockedRequest->user_id,
                        'package_id' => $lockedRequest->package_id,
                        'operator' => $lockedRequest->operator,
                        'mobile' => $lockedRequest->mobile,
                        'amount' => $lockedRequest->amount,
                        'status' => 'cancelled',
                        'description' => $description ?: 'Routed provider cancellation',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $settledStatus = 'cancelled';

                return;
            }

            $update = array_merge(['status' => 'rejected'], $baseUpdate);

            if ($validated['request_type'] === 'internet') {
                $update['description'] = $description ?: 'Request failed by provider';
            }

            $lockedRequest->update($update);

            if ($validated['request_type'] === 'drive') {
                DB::table('drive_history')->insert([
                    'user_id' => $lockedRequest->user_id,
                    'package_id' => $lockedRequest->package_id,
                    'operator' => $lockedRequest->operator,
                    'mobile' => $lockedRequest->mobile,
                    'amount' => $lockedRequest->amount,
                    'status' => 'failed',
                    'description' => $description ?: 'Routed provider failure',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $settledStatus = 'rejected';
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Routed request settled successfully.',
            'settled_status' => $settledStatus,
        ]);
    });

    Route::post('/recharge', function (Request $request) use ($ensureApiServiceEnabled, $normalizeFlexiOperatorName, $flexiOperatorNames, $resolveFlexiOperatorKeyFromMobile, $resolveRouteProductCode, $resolveSameBillingBalanceType, $resolveForwardableApiRoute, $withAvailableColumns, $incomingRoutedAttributes, $forwardRoutedRequest) {
        if ($blockedResponse = $ensureApiServiceEnabled($request, 'recharge')) {
            return $blockedResponse;
        }

        $validated = $request->validate([
            'operator' => ['nullable', 'string', 'max:100'],
            'number' => ['required', 'regex:/^01[0-9]{9}$/'],
            'amount' => ['required', 'integer', 'min:10', 'max:1499'],
            'type' => ['required', 'in:Prepaid,Postpaid'],
        ]);

        if (! Schema::hasTable('flexi_requests')) {
            return response()->json(['status' => 'error', 'message' => 'Flexi request table is not ready.'], 503);
        }

        /** @var User $user */
        $user = $request->attributes->get('api_user');
        $selectedKey = $normalizeFlexiOperatorName($validated['operator'] ?? '');
        $detectedKey = $resolveFlexiOperatorKeyFromMobile($validated['number']);
        $finalKey = $detectedKey ?: (array_key_exists($selectedKey, $flexiOperatorNames) ? $selectedKey : null);

        if (! $finalKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please choose a valid operator or enter a supported mobile number.',
            ], 422);
        }

        $amount = (int) $validated['amount'];
        $finalOperatorName = $flexiOperatorNames[$finalKey] ?? ucfirst($finalKey);
        $routeCode = $resolveRouteProductCode($finalOperatorName);
        $balanceType = $resolveSameBillingBalanceType('recharge', $routeCode, $validated['number']) ?? 'main_bal';
        $forwardRoute = $resolveForwardableApiRoute('recharge', $routeCode, $validated['number']);
        $balanceLabel = $balanceType === 'main_bal' ? 'main balance' : str_replace('_', ' ', $balanceType);

        if ((float) ($user->{$balanceType} ?? 0) < $amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient ' . $balanceLabel . '.'], 422);
        }

        $transactionId = 'FX-' . now()->format('YmdHis') . '-' . $user->id . '-' . Str::upper(Str::random(6));
        $clientDomain = $request->attributes->get('api_client_domain');

        if ($forwardRoute) {
            $flexiRequest = FlexiRequest::create($withAvailableColumns('flexi_requests', [
                'user_id' => $user->id,
                'operator' => $finalOperatorName,
                'mobile' => $validated['number'],
                'amount' => $amount,
                'cost' => $amount,
                'balance_type' => $balanceType,
                'type' => $validated['type'],
                'trnx_id' => $transactionId,
                'status' => 'pending',
            ], [
                'is_routed' => true,
                'route_api_id' => $forwardRoute->api_id,
            ]));

            $forwarded = $forwardRoutedRequest($forwardRoute, 'recharge', [
                'operator' => $finalOperatorName,
                'number' => $validated['number'],
                'amount' => $amount,
                'type' => $validated['type'],
                'source_request_id' => (string) $flexiRequest->id,
                'source_api_key' => $user->api_key,
                'source_callback_url' => url('/api/v1/routed-settlement'),
                'source_client_domain' => $clientDomain,
            ], $clientDomain);

            if (! ($forwarded['ok'] ?? false)) {
                $flexiRequest->delete();

                return response()->json([
                    'status' => 'error',
                    'message' => (string) ($forwarded['message'] ?? 'Provider request forwarding failed.'),
                ], 502);
            }

            $flexiRequest->update($withAvailableColumns('flexi_requests', [], [
                'remote_request_id' => $forwarded['request_id'],
            ]));

            app(FirebasePushNotificationService::class)->sendToAdmins(
                'New Routed API Flexiload Request',
                $user->name . ' submitted a routed API flexiload request of ' . $amount . ' for ' . $validated['number'] . '.',
                route('admin.pending.drive.requests'),
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Request Received',
                'trx_id' => $transactionId,
                'request_id' => $flexiRequest->id,
                'operator' => $finalOperatorName,
                'remaining_balance' => (float) $user->fresh()->{$balanceType},
                'balance_type' => $balanceType,
            ], 201);
        }

        $flexiRequest = null;

        DB::transaction(function () use (&$flexiRequest, $amount, $balanceType, $finalOperatorName, $transactionId, $user, $validated, $request, $incomingRoutedAttributes) {
            $flexiRequest = FlexiRequest::create([
                'user_id' => $user->id,
                'operator' => $finalOperatorName,
                'mobile' => $validated['number'],
                'amount' => $amount,
                'cost' => $amount,
                'type' => $validated['type'],
                'trnx_id' => $transactionId,
                'status' => 'pending',
            ] + $incomingRoutedAttributes($request, 'recharge'));

            $user->{$balanceType} = (float) ($user->{$balanceType} ?? 0) - $amount;
            $user->save();
        });

        app(FirebasePushNotificationService::class)->sendToAdmins(
            'New API Flexiload Request',
            $user->name . ' submitted an API flexiload request of ' . $amount . ' for ' . $validated['number'] . '.',
            route('admin.pending.drive.requests'),
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Request Received',
            'trx_id' => $transactionId,
            'request_id' => $flexiRequest?->id,
            'operator' => $finalOperatorName,
            'remaining_balance' => (float) $user->fresh()->{$balanceType},
            'balance_type' => $balanceType,
        ], 201);
    });

    Route::post('/drive', function (Request $request) use ($ensureApiServiceEnabled, $resolveRouteProductCode, $resolveSameBillingBalanceType, $resolveForwardableApiRoute, $withAvailableColumns, $incomingRoutedAttributes, $forwardRoutedRequest) {
        if ($blockedResponse = $ensureApiServiceEnabled($request, 'drive')) {
            return $blockedResponse;
        }

        $validated = $request->validate([
            'package_id' => ['required', 'integer'],
            'mobile' => ['required', 'regex:/^01[0-9]{9}$/'],
        ]);

        if (! Schema::hasTable('drive_requests')) {
            return response()->json(['status' => 'error', 'message' => 'Drive request table is not ready.'], 503);
        }

        /** @var User $user */
        $user = $request->attributes->get('api_user');
        $package = DrivePackage::query()
            ->whereKey($validated['package_id'])
            ->where('status', 'active')
            ->first();

        if (! $package) {
            return response()->json(['status' => 'error', 'message' => 'Drive package not found.'], 422);
        }

        $amount = (float) $package->price - (float) $package->commission;
        $branding = Branding::query()->first();
        $driveBalanceDisabled = (($branding->drive_balance ?? 'on') === 'off');
        $routeCode = $resolveRouteProductCode($package->operator);
        $balanceType = $driveBalanceDisabled
            ? 'main_bal'
            : ($resolveSameBillingBalanceType('drive', $routeCode, $validated['mobile']) ?? 'drive_bal');
        $forwardRoute = $resolveForwardableApiRoute('drive', $routeCode, $validated['mobile']);
        $balanceLabel = $balanceType === 'main_bal' ? 'main balance' : 'drive balance';

        if ((float) ($user->{$balanceType} ?? 0) < $amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient ' . $balanceLabel . '.'], 422);
        }

        $clientDomain = $request->attributes->get('api_client_domain');

        if ($forwardRoute) {
            $driveRequest = DriveRequest::create($withAvailableColumns('drive_requests', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'operator' => $package->operator,
                'mobile' => $validated['mobile'],
                'amount' => $amount,
                'status' => 'pending',
                'balance_type' => $balanceType,
            ], [
                'is_routed' => true,
                'route_api_id' => $forwardRoute->api_id,
            ]));

            $forwarded = $forwardRoutedRequest($forwardRoute, 'drive', [
                'package_id' => $package->id,
                'mobile' => $validated['mobile'],
                'source_request_id' => (string) $driveRequest->id,
                'source_api_key' => $user->api_key,
                'source_callback_url' => url('/api/v1/routed-settlement'),
                'source_client_domain' => $clientDomain,
            ], $clientDomain);

            if (! ($forwarded['ok'] ?? false)) {
                $driveRequest->delete();

                return response()->json([
                    'status' => 'error',
                    'message' => (string) ($forwarded['message'] ?? 'Provider request forwarding failed.'),
                ], 502);
            }

            $driveRequest->update($withAvailableColumns('drive_requests', [], [
                'remote_request_id' => $forwarded['request_id'],
            ]));

            $transactionId = 'DRV-' . $driveRequest->id . '-' . Str::upper(Str::random(6));

            app(FirebasePushNotificationService::class)->sendToAdmins(
                'New Routed API Drive Request',
                $user->name . ' submitted a routed API drive request for ' . $validated['mobile'] . '.',
                route('admin.pending.drive.requests'),
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Request Received',
                'trx_id' => $transactionId,
                'request_id' => $driveRequest->id,
                'remaining_balance' => (float) $user->fresh()->{$balanceType},
                'balance_type' => $balanceType,
            ], 201);
        }

        $driveRequest = null;

        DB::transaction(function () use (&$driveRequest, $amount, $balanceType, $package, $request, $user, $validated, $withAvailableColumns, $incomingRoutedAttributes) {
            $attributes = [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'operator' => $package->operator,
                'mobile' => $validated['mobile'],
                'amount' => $amount,
                'status' => 'pending',
            ];

            if (Schema::hasColumn('drive_requests', 'balance_type')) {
                $attributes['balance_type'] = $balanceType;
            }

            $driveRequest = DriveRequest::create($withAvailableColumns('drive_requests', $attributes, $incomingRoutedAttributes($request, 'drive')));
            $user->{$balanceType} = (float) ($user->{$balanceType} ?? 0) - $amount;
            $user->save();
        });

        $transactionId = 'DRV-' . $driveRequest->id . '-' . Str::upper(Str::random(6));

        app(FirebasePushNotificationService::class)->sendToAdmins(
            'New API Drive Request',
            $user->name . ' submitted an API drive request for ' . $validated['mobile'] . '.',
            route('admin.pending.drive.requests'),
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Request Received',
            'trx_id' => $transactionId,
            'request_id' => $driveRequest->id,
            'remaining_balance' => (float) $user->fresh()->{$balanceType},
            'balance_type' => $balanceType,
        ], 201);
    });

    Route::post('/internet', function (Request $request) use ($ensureApiServiceEnabled, $internetOperatorPrefixes, $resolveRouteProductCode, $resolveSameBillingBalanceType, $resolveForwardableApiRoute, $withAvailableColumns, $incomingRoutedAttributes, $forwardRoutedRequest) {
        if ($blockedResponse = $ensureApiServiceEnabled($request, 'internet')) {
            return $blockedResponse;
        }

        $validated = $request->validate([
            'package_id' => ['required', 'integer'],
            'mobile' => ['required', 'regex:/^01[0-9]{9}$/'],
        ]);

        if (! Schema::hasTable('regular_requests') || ! Schema::hasTable('regular_packages')) {
            return response()->json(['status' => 'error', 'message' => 'Internet request table is not ready.'], 503);
        }

        /** @var User $user */
        $user = $request->attributes->get('api_user');
        $package = RegularPackage::query()
            ->whereKey($validated['package_id'])
            ->where('status', 'active')
            ->first();

        if (! $package) {
            return response()->json(['status' => 'error', 'message' => 'Internet package not found.'], 422);
        }

        $operatorKey = strtolower(preg_replace('/[^a-z]/i', '', (string) $package->operator));
        $allowedPrefixes = $internetOperatorPrefixes[$operatorKey] ?? [];

        if ($allowedPrefixes !== [] && ! in_array(substr($validated['mobile'], 0, 3), $allowedPrefixes, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid mobile number for selected operator.',
            ], 422);
        }

        $amount = (float) $package->price - (float) $package->commission;
        $routeCode = $resolveRouteProductCode($package->operator);
        $balanceType = $resolveSameBillingBalanceType('internet', $routeCode, $validated['mobile']) ?? 'main_bal';
        $forwardRoute = $resolveForwardableApiRoute('internet', $routeCode, $validated['mobile']);
        $balanceLabel = $balanceType === 'main_bal' ? 'main balance' : str_replace('_', ' ', $balanceType);

        if ((float) ($user->{$balanceType} ?? 0) < $amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient ' . $balanceLabel . '.'], 422);
        }

        $clientDomain = $request->attributes->get('api_client_domain');

        if ($forwardRoute) {
            $regularRequest = RegularRequest::create($withAvailableColumns('regular_requests', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'operator' => $package->operator,
                'mobile' => $validated['mobile'],
                'amount' => $amount,
                'status' => 'pending',
                'balance_type' => $balanceType,
            ], [
                'is_routed' => true,
                'route_api_id' => $forwardRoute->api_id,
            ]));

            $forwarded = $forwardRoutedRequest($forwardRoute, 'internet', [
                'package_id' => $package->id,
                'mobile' => $validated['mobile'],
                'source_request_id' => (string) $regularRequest->id,
                'source_api_key' => $user->api_key,
                'source_callback_url' => url('/api/v1/routed-settlement'),
                'source_client_domain' => $clientDomain,
            ], $clientDomain);

            if (! ($forwarded['ok'] ?? false)) {
                $regularRequest->delete();

                return response()->json([
                    'status' => 'error',
                    'message' => (string) ($forwarded['message'] ?? 'Provider request forwarding failed.'),
                ], 502);
            }

            $regularRequest->update($withAvailableColumns('regular_requests', [], [
                'remote_request_id' => $forwarded['request_id'],
            ]));

            $transactionId = 'INT-' . $regularRequest->id . '-' . Str::upper(Str::random(6));

            app(FirebasePushNotificationService::class)->sendToAdmins(
                'New Routed API Internet Pack Request',
                $user->name . ' submitted a routed API internet request for ' . $validated['mobile'] . '.',
                route('admin.pending.drive.requests'),
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Request Received',
                'trx_id' => $transactionId,
                'request_id' => $regularRequest->id,
                'remaining_balance' => (float) $user->fresh()->{$balanceType},
                'balance_type' => $balanceType,
            ], 201);
        }

        $regularRequest = null;

        DB::transaction(function () use (&$regularRequest, $amount, $balanceType, $package, $request, $user, $validated, $withAvailableColumns, $incomingRoutedAttributes) {
            $regularRequest = RegularRequest::create($withAvailableColumns('regular_requests', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'operator' => $package->operator,
                'mobile' => $validated['mobile'],
                'amount' => $amount,
                'status' => 'pending',
                'balance_type' => $balanceType,
            ], $incomingRoutedAttributes($request, 'internet')));

            $user->{$balanceType} = (float) ($user->{$balanceType} ?? 0) - $amount;
            $user->save();
        });

        $transactionId = 'INT-' . $regularRequest->id . '-' . Str::upper(Str::random(6));

        app(FirebasePushNotificationService::class)->sendToAdmins(
            'New API Internet Pack Request',
            $user->name . ' submitted an API internet request for ' . $validated['mobile'] . '.',
            route('admin.pending.drive.requests'),
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Request Received',
            'trx_id' => $transactionId,
            'request_id' => $regularRequest->id,
            'remaining_balance' => (float) $user->fresh()->{$balanceType},
            'balance_type' => $balanceType,
        ], 201);
    });

    Route::post('/bkash', fn(Request $request) => $submitManualPaymentRequest($request, 'bkash', 'Bkash'));
    Route::post('/nagad', fn(Request $request) => $submitManualPaymentRequest($request, 'nagad', 'Nagad'));
    Route::post('/rocket', fn(Request $request) => $submitManualPaymentRequest($request, 'rocket', 'Rocket'));
    Route::post('/upay', fn(Request $request) => $submitManualPaymentRequest($request, 'upay', 'Upay'));
});
