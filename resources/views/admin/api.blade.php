<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Settings - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
    <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-base-200">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none"><label for="my-drawer" class="btn btn-square btn-ghost"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg></label></div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - API Settings</a></div>
            </div>
            <main class="p-6 space-y-6">
                @php
                $isEditingConnection = !empty($editingConnection);
                $openConnectionForm = $isEditingConnection || old('title') !== null || old('user_id') !== null || old('api_key') !== null || old('api_url') !== null || old('client_domain') !== null;
                @endphp
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">API Connection Settings</h1>
                        <p class="text-sm opacity-70">External provider API add, modify, balance check, ar route action ekhan thekei manage korun.</p>
                    </div>
                    <div class="badge badge-primary badge-lg">{{ $stats['total_connections'] }} Saved APIs</div>
                </div>
                @if(session('success'))<div class="alert alert-success"><span>{{ session('success') }}</span></div>@endif
                @if(session('error'))<div class="alert alert-error"><span>{{ session('error') }}</span></div>@endif
                @if(!empty($schemaWarnings))
                <div class="alert alert-warning text-sm">
                    <span>API settings schema fully ready noy: {{ implode(', ', $schemaWarnings) }}. Full feature use korte `php artisan migrate` run korun.</span>
                </div>
                @endif
                @if($errors->any())
                <div class="alert alert-error">
                    <ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
                @endif
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-2">
                    <div class="stat bg-base-100 rounded-2xl shadow">
                        <div class="stat-title">Saved APIs</div>
                        <div class="stat-value text-secondary">{{ $stats['total_connections'] }}</div>
                    </div>
                    <div class="stat bg-base-100 rounded-2xl shadow">
                        <div class="stat-title">Active APIs</div>
                        <div class="stat-value text-accent">{{ $stats['active_connections'] }}</div>
                    </div>
                </div>
                <div class="card bg-base-100 shadow-xl" id="api-connection-form">
                    <div class="card-body space-y-5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="card-title text-2xl">API Connection Management</h2>
                                <p class="text-sm opacity-70">External provider API add, modify, balance check, ar route action ekhane manage korun.</p>
                            </div>
                            <details class="dropdown dropdown-end" {{ $openConnectionForm ? 'open' : '' }}>
                                <summary class="btn btn-primary">{{ $isEditingConnection ? 'Modify API' : 'Add API' }}</summary>
                                <div class="dropdown-content z-[1] mt-3 w-[min(100vw-3rem,40rem)] rounded-2xl border border-base-300 bg-base-100 p-0 shadow-2xl">
                                    <div class="card-body space-y-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 class="text-xl font-semibold">API Information</h3>
                                                <p class="text-sm opacity-70">Api Title, user ID, API key, provider, URL, client domain, ar status set korun.</p>
                                            </div>
                                            @if($isEditingConnection)
                                            <a href="{{ route('api.index') }}#api-connection-form" class="btn btn-sm btn-ghost">Cancel</a>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ $isEditingConnection ? route('api.connections.update', $editingConnection) : route('api.connections.store') }}" class="space-y-4">
                                            @csrf
                                            @if($isEditingConnection)
                                            @method('PUT')
                                            @endif
                                            @if(!$hasApiConnectionClientDomainColumn)
                                            <div class="alert alert-info text-sm">
                                                <span>Client domain override use korte `php artisan migrate` run kore latest API connection column add korun.</span>
                                            </div>
                                            @endif
                                            <div class="grid gap-4 md:grid-cols-2">
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">Api Title</span></label>
                                                    <input type="text" name="title" class="input input-bordered" value="{{ old('title', $editingConnection->title ?? '') }}" placeholder="Same Billing Main" required />
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">API user ID</span></label>
                                                    <input type="text" name="user_id" class="input input-bordered" value="{{ old('user_id', $editingConnection->user_id ?? '') }}" placeholder="provider-user-101" required />
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">API key</span></label>
                                                    <input type="text" name="api_key" class="input input-bordered" value="{{ old('api_key', $editingConnection->api_key ?? '') }}" placeholder="Enter API key" required />
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">Provider</span></label>
                                                    <select name="provider" class="select select-bordered" required>
                                                        @foreach($connectionProviderOptions as $providerValue => $providerLabel)
                                                        <option value="{{ $providerValue }}" {{ old('provider', $editingConnection->provider ?? 'same billing') === $providerValue ? 'selected' : '' }}>{{ $providerLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-control">
                                                <label class="label"><span class="label-text">API url</span></label>
                                                <input type="text" name="api_url" class="input input-bordered" value="{{ old('api_url', $editingConnection->api_url ?? '') }}" placeholder="https://provider.example.com/balance" required />
                                            </div>
                                            @if($hasApiConnectionClientDomainColumn)
                                            <div class="form-control">
                                                <label class="label"><span class="label-text">Client domain (optional)</span></label>
                                                <input type="text" name="client_domain" class="input input-bordered" value="{{ old('client_domain', $editingConnection->client_domain ?? '') }}" placeholder="yourdomain.com" />
                                                <label class="label"><span class="label-text-alt">Provider whitelist use korle ekhane public domain din. Localhost theke direct check fail korte pare.</span></label>
                                            </div>
                                            @endif
                                            <div class="rounded-2xl bg-base-200 p-4">
                                                <input type="hidden" name="status" value="deactive" />
                                                <label class="flex items-center justify-between gap-4">
                                                    <div>
                                                        <span class="font-medium block">Active / Deactive</span>
                                                        <span class="text-xs opacity-70">Active hole route ar balance check cholbe, deactive hole connection stop thakbe.</span>
                                                    </div>
                                                    <input type="checkbox" name="status" value="active" class="toggle toggle-success toggle-lg" {{ old('status', $editingConnection->status ?? 'active') === 'active' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                @if($isEditingConnection)
                                                <a href="{{ route('api.index') }}#api-connection-form" class="btn btn-outline">Back</a>
                                                @endif
                                                <button type="submit" class="btn btn-primary">Save Change</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        </div>
                        <div class="overflow-x-auto rounded-2xl border border-base-300">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Nr</th>
                                        <th>Title</th>
                                        <th>userid</th>
                                        <th>Provider</th>
                                        <th>status</th>
                                        <th>approval</th>
                                        <th>main balance</th>
                                        <th>drive balance</th>
                                        <th>bank balance</th>
                                        <th class="min-w-[18rem]">action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($apiConnections as $index => $connection)
                                    @php
                                    $approvalStatus = $hasApiConnectionApprovalsTable ? $connection->approvalStatus() : 0;
                                    $showBalanceSnapshot = $hasApiConnectionBalanceSnapshotColumns && $approvalStatus === 1;
                                    $formatBalance = static fn ($value) => $showBalanceSnapshot && $value !== null
                                    ? '৳ ' . number_format((float) $value, 2)
                                    : '—';
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="font-semibold">{{ $connection->title }}</div>
                                            <div class="text-xs opacity-60 break-all">{{ $connection->api_url ?: 'No URL saved' }}</div>
                                            @if($hasApiConnectionClientDomainColumn && filled($connection->client_domain))
                                            <div class="text-xs opacity-60 break-all">Client domain: {{ $connection->client_domain }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $connection->user_id }}</td>
                                        <td>{{ $connection->provider }}</td>
                                        <td>
                                            <span class="badge {{ $connection->status === 'active' ? 'badge-success' : 'badge-error' }}">{{ $connection->status === 'active' ? 'active' : 'deactive' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $approvalStatus === 1 ? 'badge-success' : 'badge-error' }}">{{ $approvalStatus === 1 ? 'active' : 'deactive' }}</span>
                                        </td>
                                        <td>{{ $formatBalance($connection->main_balance) }}</td>
                                        <td>{{ $formatBalance($connection->drive_balance) }}</td>
                                        <td>{{ $formatBalance($connection->bank_balance) }}</td>
                                        <td>
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('api.connections.route', $connection) }}" class="btn btn-xs btn-outline">Route</a>
                                                <form method="POST" action="{{ route('api.connections.balance', $connection) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-info text-white" {{ $connection->status !== 'active' ? 'disabled' : '' }}>Balance check</button>
                                                </form>
                                                <a href="{{ route('api.index', ['edit_connection' => $connection->id]) }}#api-connection-form" class="btn btn-xs btn-warning">Modify</a>
                                                <form method="POST" action="{{ route('api.connections.destroy', $connection) }}" onsubmit="return confirm('Delete this API connection?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-error text-white">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-base-content/60">No API connection added yet.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div class="drawer-side z-40">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 min-h-screen border-r border-base-200">
                <div class="p-4 border-b border-base-200"><a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2"><span class="text-lg font-bold sidebar-text">{{ optional($settings)->company_name ?? 'Codecartel' }}</span></a></div>
                <ul class="menu p-4 gap-1">
                    <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li>
                        <details open>
                            <summary><span class="sidebar-text">Administration</span></summary>
                            <ul>
                                <li><a href="{{ route('api.index') }}" class="active">Api Settings</a></li>
                                <li><a href="{{ route('admin.payment.gateway') }}">Payment Gateway</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><span class="sidebar-text">Tools</span></summary>
                            <ul>
                                <li><a href="{{ route('admin.branding') }}">Branding</a></li>
                                <li><a href="{{ route('admin.device.logs') }}">Device Logs</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><span class="sidebar-text">Settings</span></summary>
                            <ul>
                                <li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li>
                                <li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li>
                                <li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li>
                                <li><a href="{{ route('admin.firebase.config') }}">Firebase Credentials</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">Logout</button></form>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</body>

</html>