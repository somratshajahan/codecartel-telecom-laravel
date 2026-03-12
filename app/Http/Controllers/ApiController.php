<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Models\ApiRoute;
use App\Models\HomepageSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    public function index(Request $request)
    {
        $settings = HomepageSetting::first();
        $hasApiKeyColumn = Schema::hasColumn('users', 'api_key');
        $hasApiAccessEnabledColumn = Schema::hasColumn('users', 'api_access_enabled');
        $hasApiServicesColumn = Schema::hasColumn('users', 'api_services');
        $hasApiDomainsTable = Schema::hasTable('api_domains');
        $hasApiConnectionsTable = Schema::hasTable('apis');
        $hasApiConnectionKeyColumn = $hasApiConnectionsTable && Schema::hasColumn('apis', 'api_key');
        $hasApiConnectionUrlColumn = $hasApiConnectionsTable && Schema::hasColumn('apis', 'api_url');
        $hasApiConnectionClientDomainColumn = $hasApiConnectionsTable && Schema::hasColumn('apis', 'client_domain');
        $hasApiConnectionApprovalsTable = Schema::hasTable('api_connection_approvals');
        $hasApiConnectionMainBalanceColumn = $hasApiConnectionsTable && Schema::hasColumn('apis', 'main_balance');
        $hasApiConnectionDriveBalanceColumn = $hasApiConnectionsTable && Schema::hasColumn('apis', 'drive_balance');
        $hasApiConnectionBankBalanceColumn = $hasApiConnectionsTable && Schema::hasColumn('apis', 'bank_balance');
        $hasApiConnectionBalanceSnapshotColumns = $hasApiConnectionMainBalanceColumn
            && $hasApiConnectionDriveBalanceColumn
            && $hasApiConnectionBankBalanceColumn;

        $apiUsersQuery = User::query()
            ->where('is_admin', false);

        if ($hasApiDomainsTable) {
            $apiUsersQuery->with('apiDomains');
        }

        if ($hasApiAccessEnabledColumn) {
            $apiUsersQuery->orderByDesc('api_access_enabled');
        }

        $apiUsers = $apiUsersQuery
            ->orderByDesc('id')
            ->get();

        if (! $hasApiDomainsTable) {
            $apiUsers->each(fn(User $user) => $user->setRelation('apiDomains', collect()));
        }

        $schemaWarnings = [];

        if (! $hasApiKeyColumn) {
            $schemaWarnings[] = 'users.api_key column missing';
        }

        if (! $hasApiAccessEnabledColumn) {
            $schemaWarnings[] = 'users.api_access_enabled column missing';
        }

        if (! $hasApiServicesColumn) {
            $schemaWarnings[] = 'users.api_services column missing';
        }

        if (! $hasApiDomainsTable) {
            $schemaWarnings[] = 'api_domains table missing';
        }

        if (! $hasApiConnectionsTable) {
            $schemaWarnings[] = 'apis table missing';
        }

        if ($hasApiConnectionsTable && ! $hasApiConnectionKeyColumn) {
            $schemaWarnings[] = 'apis.api_key column missing';
        }

        if ($hasApiConnectionsTable && ! $hasApiConnectionUrlColumn) {
            $schemaWarnings[] = 'apis.api_url column missing';
        }

        if (! $hasApiConnectionApprovalsTable) {
            $schemaWarnings[] = 'api_connection_approvals table missing';
        }

        if ($hasApiConnectionsTable && ! $hasApiConnectionMainBalanceColumn) {
            $schemaWarnings[] = 'apis.main_balance column missing';
        }

        if ($hasApiConnectionsTable && ! $hasApiConnectionDriveBalanceColumn) {
            $schemaWarnings[] = 'apis.drive_balance column missing';
        }

        if ($hasApiConnectionsTable && ! $hasApiConnectionBankBalanceColumn) {
            $schemaWarnings[] = 'apis.bank_balance column missing';
        }

        $apiServiceOptions = User::apiServiceOptions();
        $apiConnectionsQuery = Api::query();

        if ($hasApiConnectionApprovalsTable) {
            $apiConnectionsQuery->with('approval');
        }

        $apiConnections = $hasApiConnectionsTable
            ? $apiConnectionsQuery->latest('id')->get()
            : collect();
        $editingConnection = null;

        if ($hasApiConnectionsTable && filled($requestedConnectionId = $request->query('edit_connection'))) {
            $editingConnectionQuery = Api::query();

            if ($hasApiConnectionApprovalsTable) {
                $editingConnectionQuery->with('approval');
            }

            $editingConnection = $editingConnectionQuery->find($requestedConnectionId);
        }

        $connectionProviderOptions = $this->connectionProviderOptions();
        $stats = [
            'total_users' => $apiUsers->count(),
            'approved_users' => $hasApiAccessEnabledColumn ? $apiUsers->where('api_access_enabled', true)->count() : 0,
            'pending_users' => $hasApiAccessEnabledColumn ? $apiUsers->where('api_access_enabled', false)->count() : $apiUsers->count(),
            'total_domains' => $hasApiDomainsTable ? $apiUsers->sum(fn(User $user) => $user->apiDomains->count()) : 0,
            'total_connections' => $apiConnections->count(),
            'active_connections' => $hasApiConnectionApprovalsTable
                ? $apiConnections->filter(fn(Api $connection) => $connection->approvalStatus() === 1)->count()
                : 0,
        ];

        return view('admin.api', compact(
            'settings',
            'apiUsers',
            'apiServiceOptions',
            'stats',
            'schemaWarnings',
            'apiConnections',
            'editingConnection',
            'connectionProviderOptions',
            'hasApiConnectionApprovalsTable',
            'hasApiConnectionClientDomainColumn',
            'hasApiConnectionBalanceSnapshotColumns',
        ));
    }

    public function store(Request $request, User $user)
    {
        if ($user->is_admin) {
            return redirect()->route('api.index')->with('error', 'Admin accounts are not managed from API settings.');
        }

        if (! Schema::hasColumn('users', 'api_access_enabled') || ! Schema::hasColumn('users', 'api_services')) {
            return redirect()->route('api.index')->with('error', 'API access columns are missing in the users table. Please run php artisan migrate first.');
        }

        $validated = $request->validate([
            'services' => ['nullable', 'array'],
            'services.*' => ['string', Rule::in(array_keys(User::apiServiceOptions()))],
        ]);

        $user->forceFill([
            'api_access_enabled' => $request->boolean('api_access_enabled'),
            'api_services' => array_values($validated['services'] ?? []),
        ])->save();

        return redirect()->route('api.index')->with('success', 'API settings updated successfully!');
    }

    public function storeConnection(Request $request)
    {
        if ($schemaError = $this->apiConnectionSchemaError()) {
            return redirect()->route('api.index')->with('error', $schemaError);
        }

        Api::query()->create($this->validatedConnectionData($request));

        return redirect()->route('api.index')->with('success', 'API connection saved successfully!');
    }

    public function updateConnection(Request $request, Api $connection)
    {
        if ($schemaError = $this->apiConnectionSchemaError()) {
            return redirect()->route('api.index')->with('error', $schemaError);
        }

        $connection->fill($this->validatedConnectionData($request))->save();

        return redirect()->route('api.index')->with('success', 'API connection updated successfully!');
    }

    public function destroyConnection(Api $connection)
    {
        if (Schema::hasTable('api_connection_approvals')) {
            $connection->approval()->delete();
        }

        $connection->delete();

        return redirect()->route('api.index')->with('success', 'API connection deleted successfully!');
    }

    public function openConnectionRoute(Api $connection)
    {
        return redirect()->route('api.routes.index', ['connection' => $connection->id]);
    }

    public function routeIndex(Request $request)
    {
        $settings = HomepageSetting::first();
        $hasApiConnectionsTable = Schema::hasTable('apis');
        $hasApiRoutesTable = Schema::hasTable('api_routes');

        $apiConnections = $hasApiConnectionsTable
            ? Api::query()->latest('id')->get()
            : collect();

        $selectedConnection = $hasApiConnectionsTable && filled($requestedConnectionId = $request->query('connection'))
            ? Api::query()->find($requestedConnectionId)
            : null;

        $apiRoutes = $hasApiRoutesTable
            ? ApiRoute::query()->with('apiConnection')->orderBy('priority')->orderByDesc('id')->get()
            : collect();

        $editingApiRoute = $hasApiRoutesTable && filled($requestedRouteId = $request->query('edit_route'))
            ? ApiRoute::query()->with('apiConnection')->find($requestedRouteId)
            : null;

        $schemaWarnings = [];

        if (! $hasApiConnectionsTable) {
            $schemaWarnings[] = 'apis table missing';
        }

        if (! $hasApiRoutesTable) {
            $schemaWarnings[] = 'api_routes table missing';
        }

        $routeServiceOptions = $this->routeServiceOptions();
        $routeCodeOptions = $this->routeCodeOptions();
        $routeModuleOptions = $this->routeModuleOptions($apiConnections);
        $routeStats = [
            'total_routes' => $apiRoutes->count(),
            'active_routes' => $apiRoutes->where('status', 'active')->count(),
        ];

        return view('admin.api-routes', compact(
            'settings',
            'apiConnections',
            'selectedConnection',
            'apiRoutes',
            'editingApiRoute',
            'schemaWarnings',
            'routeServiceOptions',
            'routeCodeOptions',
            'routeModuleOptions',
            'routeStats',
        ));
    }

    public function storeRoute(Request $request)
    {
        if ($schemaError = $this->apiRouteSchemaError()) {
            return redirect()->route('api.routes.index', $this->routeRedirectParameters($request))
                ->with('error', $schemaError);
        }

        ApiRoute::query()->create($this->validatedRouteData($request));

        return redirect()->route('api.routes.index', $this->routeRedirectParameters($request))
            ->with('success', 'API route saved successfully!');
    }

    public function updateRoute(Request $request, ApiRoute $apiRoute)
    {
        if ($schemaError = $this->apiRouteSchemaError()) {
            return redirect()->route('api.routes.index', $this->routeRedirectParameters($request))
                ->with('error', $schemaError);
        }

        $apiRoute->fill($this->validatedRouteData($request))->save();

        return redirect()->route('api.routes.index', $this->routeRedirectParameters($request))
            ->with('success', 'API route updated successfully!');
    }

    public function destroyRoute(Request $request, ApiRoute $apiRoute)
    {
        $apiRoute->delete();

        return redirect()->route('api.routes.index', $this->routeRedirectParameters($request))
            ->with('success', 'API route deleted successfully!');
    }

    public function balanceCheck(Request $request, Api $connection)
    {
        if ($schemaError = $this->apiConnectionSchemaError()) {
            return redirect()->route('api.index')->with('error', $schemaError);
        }

        if ($connection->status !== 'active') {
            return redirect()->route('api.index')->with('error', 'This API connection is deactive. Please active it first.');
        }

        $requestUrls = $this->resolveBalanceCheckUrls($connection);

        if ($requestUrls === []) {
            $this->clearConnectionBalances($connection);
            $this->syncConnectionApproval($connection, 0);

            return redirect()->route('api.index')->with('error', 'API URL is invalid for this connection.');
        }

        $payload = [
            'user_id' => $connection->user_id,
            'api_user_id' => $connection->user_id,
            'api_key' => $connection->api_key,
        ];

        $headers = [
            'Accept' => 'application/json',
            'X-API-KEY' => $connection->api_key,
            'Authorization' => 'Bearer ' . $connection->api_key,
        ];

        $clientDomain = $this->resolveConnectionClientDomain($request, $connection);

        if ($clientDomain) {
            $headers['X-Client-Domain'] = $clientDomain;
            $payload['domain'] = $clientDomain;
        }

        $lastError = 'Balance check failed. Could not read a balance response from the provider.';
        $preferredError = null;

        foreach ($requestUrls as $requestUrl) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders($headers)
                    ->post($requestUrl, $payload);
            } catch (\Throwable $exception) {
                $lastError = 'Balance check failed: ' . $exception->getMessage();
                continue;
            }

            if (! $response->successful()) {
                $lastError = 'Balance check failed. Provider returned HTTP ' . $response->status() . '.';

                if ($response->status() === 403) {
                    $preferredError ??= $clientDomain
                        ? 'Balance check failed. Provider returned HTTP 403 for client domain ' . $clientDomain . '. Verify this domain is whitelisted on the provider side.'
                        : 'Balance check failed. Provider returned HTTP 403. This provider may require a public whitelisted client domain. Save the allowed client domain in this connection and try again.';
                }

                continue;
            }

            $responsePayload = $response->json();

            if ($responsePayload === null) {
                $responsePayload = $response->body();
            }

            if ($this->responseSignalsFailure($responsePayload)) {
                $lastError = is_array($responsePayload) && filled($responsePayload['message'] ?? null)
                    ? (string) $responsePayload['message']
                    : 'Balance check failed. Provider returned an unsuccessful response.';
                $preferredError = $lastError;
                continue;
            }

            $snapshot = $this->extractBalanceSnapshot($responsePayload);

            if (
                $snapshot['balance'] === null
                && $snapshot['main_balance'] === null
                && $snapshot['drive_balance'] === null
                && $snapshot['bank_balance'] === null
            ) {
                $lastError = 'Balance response did not include a readable balance.';
                continue;
            }

            $this->saveConnectionBalanceSnapshot($connection, $snapshot);
            $this->syncConnectionApproval($connection, 1);

            return redirect()->route('api.index')->with('success', 'Balance checked successfully for ' . $connection->title . '.');
        }

        $this->clearConnectionBalances($connection);
        $this->syncConnectionApproval($connection, 0);

        return redirect()->route('api.index')->with('error', $preferredError ?? $lastError);
    }

    protected function validatedConnectionData(Request $request): array
    {
        $hasClientDomainColumn = Schema::hasTable('apis') && Schema::hasColumn('apis', 'client_domain');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', Rule::in(array_keys($this->connectionProviderOptions()))],
            'api_url' => ['required', 'string', 'max:2048'],
            'status' => ['required', 'string', Rule::in(['active', 'deactive'])],
        ];

        if ($hasClientDomainColumn) {
            $rules['client_domain'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        $rawClientDomain = trim((string) ($validated['client_domain'] ?? ''));
        $clientDomain = null;

        if ($hasClientDomainColumn && $rawClientDomain !== '') {
            $clientDomain = $this->normalizeDomain($rawClientDomain);

            if ($clientDomain === null) {
                throw ValidationException::withMessages([
                    'client_domain' => 'Enter a valid public domain like example.com.',
                ]);
            }
        }

        $data = [
            'title' => trim($validated['title']),
            'user_id' => trim($validated['user_id']),
            'api_key' => trim($validated['api_key']),
            'provider' => $validated['provider'],
            'api_url' => trim($validated['api_url']),
            'status' => $validated['status'],
        ];

        if ($hasClientDomainColumn) {
            $data['client_domain'] = $clientDomain;
        }

        return $data;
    }

    protected function apiConnectionSchemaError(): ?string
    {
        if (! Schema::hasTable('apis')) {
            return 'API connection table is missing. Please run php artisan migrate first.';
        }

        if (! Schema::hasColumn('apis', 'api_key') || ! Schema::hasColumn('apis', 'api_url')) {
            return 'API connection columns are missing in the apis table. Please run php artisan migrate first.';
        }

        if (! Schema::hasTable('api_connection_approvals')) {
            return 'API connection approval table is missing. Please run php artisan migrate first.';
        }

        if (
            ! Schema::hasColumn('apis', 'main_balance')
            || ! Schema::hasColumn('apis', 'drive_balance')
            || ! Schema::hasColumn('apis', 'bank_balance')
        ) {
            return 'API connection balance snapshot columns are missing in the apis table. Please run php artisan migrate first.';
        }

        return null;
    }

    protected function apiRouteSchemaError(): ?string
    {
        if (! Schema::hasTable('api_routes')) {
            return 'API route table is missing. Please run php artisan migrate first.';
        }

        return null;
    }

    protected function connectionProviderOptions(): array
    {
        return [
            'same billing' => 'same billing',
            'Ecare Technology' => 'Ecare Technology',
        ];
    }

    protected function routeServiceOptions(): array
    {
        return [
            'all' => 'All service',
            'recharge' => 'Flexiload',
            'internet' => 'Internet pack',
            'drive' => 'drive',
            'bkash' => 'bkash',
            'nagad' => 'nagad',
            'rocket' => 'rocket',
            'upay' => 'upay',
        ];
    }

    protected function routeCodeOptions(): array
    {
        return [
            'all' => 'All Product Code',
            'Gp' => 'Gp',
            'RB' => 'RB',
            'AT' => 'AT',
            'TT' => 'TT',
            'BL' => 'BL',
            'SK' => 'SK',
        ];
    }

    protected function routeModuleOptions(iterable $apiConnections): array
    {
        $options = [
            'manual' => 'manul System',
        ];

        foreach ($apiConnections as $connection) {
            $options['api:' . $connection->id] = $connection->title;
        }

        return $options;
    }

    protected function validatedRouteData(Request $request): array
    {
        $apiConnections = Schema::hasTable('apis')
            ? Api::query()->get(['id', 'title'])
            : collect();

        $routeModuleOptions = $this->routeModuleOptions($apiConnections);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'module' => ['required', 'string', Rule::in(array_keys($routeModuleOptions))],
            'service' => ['required', 'string', Rule::in(array_keys($this->routeServiceOptions()))],
            'code' => ['required', 'string', Rule::in(array_keys($this->routeCodeOptions()))],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'prefix' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['active', 'deactive'])],
        ]);

        [$moduleType, $moduleName, $apiId] = $this->resolveRouteModuleSelection($validated['module'], $apiConnections);

        return [
            'title' => trim($validated['title']),
            'module_type' => $moduleType,
            'module_name' => $moduleName,
            'api_id' => $apiId,
            'service' => $validated['service'],
            'code' => $validated['code'],
            'priority' => (int) $validated['priority'],
            'prefix' => filled(trim((string) ($validated['prefix'] ?? ''))) ? trim((string) $validated['prefix']) : null,
            'status' => $validated['status'],
        ];
    }

    protected function resolveRouteModuleSelection(string $selection, iterable $apiConnections): array
    {
        if ($selection === 'manual') {
            return ['manual', 'manul System', null];
        }

        foreach ($apiConnections as $connection) {
            if ($selection === 'api:' . $connection->id) {
                return ['api', $connection->title, $connection->id];
            }
        }

        return ['manual', 'manul System', null];
    }

    protected function routeRedirectParameters(Request $request): array
    {
        $connectionId = $request->input('context_connection_id');

        return filled($connectionId)
            ? ['connection' => $connectionId]
            : [];
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return 'https://' . ltrim($url, '/');
    }

    protected function resolveBalanceCheckUrls(Api $connection): array
    {
        $baseUrl = $this->normalizeUrl((string) $connection->api_url);

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return [];
        }

        $urls = [$baseUrl];
        $path = (string) parse_url($baseUrl, PHP_URL_PATH);

        if ($path !== '' && preg_match('#/(balance|auth-check|recharge|drive|internet)$#i', $path)) {
            $urls[] = preg_replace('#/(balance|auth-check|recharge|drive|internet)$#i', '/balance', $baseUrl) ?: $baseUrl;
        }

        if (! str_contains(strtolower($path), 'balance')) {
            $urls[] = rtrim($baseUrl, '/') . '/balance';
        }

        $scheme = (string) parse_url($baseUrl, PHP_URL_SCHEME);
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);
        $port = parse_url($baseUrl, PHP_URL_PORT);

        if ($scheme !== '' && $host !== '') {
            $origin = $scheme . '://' . $host . ($port ? ':' . $port : '');
            $urls[] = rtrim($origin, '/') . '/api/v1/balance';
        }

        return array_values(array_unique(array_filter($urls, fn($url) => filter_var($url, FILTER_VALIDATE_URL))));
    }

    protected function resolveConnectionClientDomain(Request $request, ?Api $connection = null): ?string
    {
        if (
            $connection
            && Schema::hasTable('apis')
            && Schema::hasColumn('apis', 'client_domain')
            && filled($connection->client_domain)
        ) {
            $configuredDomain = $this->normalizeDomain((string) $connection->client_domain);

            if ($configuredDomain !== null) {
                return $configuredDomain;
            }
        }

        $candidates = [
            $request->getHost(),
            parse_url((string) config('app.url'), PHP_URL_HOST),
            (string) config('app.url'),
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

    protected function extractBalance(mixed $payload): ?float
    {
        if (is_numeric($payload)) {
            return (float) $payload;
        }

        if (! is_array($payload)) {
            return null;
        }

        foreach ($payload as $key => $value) {
            if (is_string($key) && str_contains(strtolower($key), 'balance') && is_numeric($value)) {
                return (float) $value;
            }

            if (is_array($value)) {
                $nestedBalance = $this->extractBalance($value);

                if ($nestedBalance !== null) {
                    return $nestedBalance;
                }
            }
        }

        return null;
    }

    protected function responseSignalsFailure(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        $status = strtolower(trim((string) ($payload['status'] ?? '')));

        if (in_array($status, ['error', 'failed', 'fail', 'deactive', 'inactive'], true)) {
            return true;
        }

        if (array_key_exists('success', $payload) && ! (bool) $payload['success']) {
            return true;
        }

        return false;
    }

    protected function extractBalanceSnapshot(mixed $payload): array
    {
        $genericBalance = $this->extractBalance($payload);
        $mainBalance = $this->extractBalanceByKeywords($payload, ['main balance', 'main_balance', 'mainbal']);
        $driveBalance = $this->extractBalanceByKeywords($payload, ['drive balance', 'drive_balance', 'drivebal']);
        $bankBalance = $this->extractBalanceByKeywords($payload, ['bank balance', 'bank_balance', 'bankbal']);

        if ($genericBalance !== null) {
            $mainBalance ??= $genericBalance;
            $driveBalance ??= $genericBalance;
            $bankBalance ??= $genericBalance;
        }

        return [
            'balance' => $genericBalance ?? $mainBalance ?? $driveBalance ?? $bankBalance,
            'main_balance' => $mainBalance,
            'drive_balance' => $driveBalance,
            'bank_balance' => $bankBalance,
        ];
    }

    protected function extractBalanceByKeywords(mixed $payload, array $keywords): ?float
    {
        if (! is_array($payload)) {
            return null;
        }

        $normalizedKeywords = array_map(
            static fn(string $keyword): string => strtolower(preg_replace('/[^a-z]/', '', $keyword)),
            $keywords,
        );

        foreach ($payload as $key => $value) {
            $normalizedKey = is_string($key)
                ? strtolower(preg_replace('/[^a-z]/', '', $key))
                : '';

            if ($normalizedKey !== '' && is_numeric($value)) {
                foreach ($normalizedKeywords as $keyword) {
                    if ($keyword !== '' && str_contains($normalizedKey, $keyword)) {
                        return (float) $value;
                    }
                }
            }

            if (is_array($value)) {
                $nestedBalance = $this->extractBalanceByKeywords($value, $keywords);

                if ($nestedBalance !== null) {
                    return $nestedBalance;
                }
            }
        }

        return null;
    }

    protected function saveConnectionBalanceSnapshot(Api $connection, array $snapshot): void
    {
        $connection->forceFill([
            'balance' => (float) ($snapshot['balance'] ?? 0),
            'main_balance' => $snapshot['main_balance'],
            'drive_balance' => $snapshot['drive_balance'],
            'bank_balance' => $snapshot['bank_balance'],
        ])->save();
    }

    protected function clearConnectionBalances(Api $connection): void
    {
        $connection->forceFill([
            'balance' => 0,
            'main_balance' => null,
            'drive_balance' => null,
            'bank_balance' => null,
        ])->save();
    }

    protected function syncConnectionApproval(Api $connection, int $status): void
    {
        $connection->approval()->updateOrCreate(
            ['api_id' => $connection->id],
            ['status' => $status],
        );

        $connection->unsetRelation('approval');
        $connection->load('approval');
    }
}
