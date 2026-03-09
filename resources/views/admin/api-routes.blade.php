<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Routes - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-none"><label for="my-drawer" class="btn btn-square btn-ghost"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg></label></div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - API Routes</a></div>
            </div>
            <main class="p-6 space-y-6">
                @php
                $isEditingRoute = !empty($editingApiRoute);
                $openRouteForm = $isEditingRoute || old('title') !== null || old('module') !== null || old('service') !== null || old('code') !== null || old('priority') !== null || old('prefix') !== null;
                $selectedModule = old('module');
                if ($selectedModule === null) {
                    if ($isEditingRoute) {
                        $selectedModule = $editingApiRoute->module_type === 'api' && $editingApiRoute->api_id ? 'api:' . $editingApiRoute->api_id : 'manual';
                    } elseif ($selectedConnection) {
                        $selectedModule = 'api:' . $selectedConnection->id;
                    } else {
                        $selectedModule = 'manual';
                    }
                }
                @endphp

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="breadcrumbs text-sm opacity-70"><ul><li><a href="{{ route('api.index') }}">API Settings</a></li><li>Routes</li></ul></div>
                        <h1 class="text-3xl font-bold">API Routing Management</h1>
                        <p class="text-sm opacity-70">Route button click korle ei page-e ashbe. Ekhane routing rule add, modify, ar delete korte parben.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <div class="badge badge-secondary badge-lg">{{ $routeStats['total_routes'] }} Saved Routes</div>
                        <a href="{{ route('api.index') }}" class="btn btn-outline">Back to API Settings</a>
                    </div>
                </div>

                @if(session('success'))<div class="alert alert-success"><span>{{ session('success') }}</span></div>@endif
                @if(session('error'))<div class="alert alert-error"><span>{{ session('error') }}</span></div>@endif
                @if(!empty($schemaWarnings))
                <div class="alert alert-warning text-sm"><span>Routing settings schema fully ready noy: {{ implode(', ', $schemaWarnings) }}. Full feature use korte `php artisan migrate` run korun.</span></div>
                @endif
                @if($errors->any())
                <div class="alert alert-error"><ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="stat bg-base-100 rounded-2xl shadow"><div class="stat-title">Saved Routes</div><div class="stat-value text-secondary">{{ $routeStats['total_routes'] }}</div></div>
                    <div class="stat bg-base-100 rounded-2xl shadow"><div class="stat-title">Active Routes</div><div class="stat-value text-accent">{{ $routeStats['active_routes'] }}</div></div>
                </div>

                @if($selectedConnection)
                <div class="alert {{ $selectedConnection->status === 'active' ? 'alert-info' : 'alert-warning' }} text-sm">
                    <span>Selected API: <strong>{{ $selectedConnection->title }}</strong> · User ID: {{ $selectedConnection->user_id }} · Status: {{ $selectedConnection->status }}</span>
                </div>
                @endif

                <div class="card bg-base-100 shadow-xl" id="api-route-form">
                    <div class="card-body space-y-5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="card-title text-2xl">Route List & Setup</h2>
                                <p class="text-sm opacity-70">Module, service, code, priority, prefix, ar status diye route rule set korun.</p>
                            </div>
                            <details class="dropdown dropdown-end" {{ $openRouteForm ? 'open' : '' }}>
                                <summary class="btn btn-primary">{{ $isEditingRoute ? 'Modify Route' : 'Add Route' }}</summary>
                                <div class="dropdown-content z-[1] mt-3 w-[min(100vw-3rem,42rem)] rounded-2xl border border-base-300 bg-base-100 p-0 shadow-2xl">
                                    <div class="card-body space-y-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 class="text-xl font-semibold">Routing Information</h3>
                                                <p class="text-sm opacity-70">Routing Title, module, service, code, priority, prefix, ar status set korun.</p>
                                            </div>
                                            @if($isEditingRoute)
                                            <a href="{{ route('api.routes.index', array_filter(['connection' => $selectedConnection->id ?? null])) }}#api-route-form" class="btn btn-sm btn-ghost">Cancel</a>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ $isEditingRoute ? route('api.routes.update', $editingApiRoute) : route('api.routes.store') }}" class="space-y-4">
                                            @csrf
                                            @if($isEditingRoute)
                                            @method('PUT')
                                            @endif
                                            <input type="hidden" name="context_connection_id" value="{{ $selectedConnection->id ?? old('context_connection_id') }}" />
                                            <div class="grid gap-4 md:grid-cols-2">
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">Routing Title</span></label>
                                                    <input type="text" name="title" class="input input-bordered" value="{{ old('title', $editingApiRoute->title ?? '') }}" placeholder="Primary Flexi Route" required />
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">Module</span></label>
                                                    <select name="module" class="select select-bordered" required>
                                                        @foreach($routeModuleOptions as $moduleValue => $moduleLabel)
                                                        <option value="{{ $moduleValue }}" {{ $selectedModule === $moduleValue ? 'selected' : '' }}>{{ $moduleLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">Service</span></label>
                                                    <select name="service" class="select select-bordered" required>
                                                        @foreach($routeServiceOptions as $serviceValue => $serviceLabel)
                                                        <option value="{{ $serviceValue }}" {{ old('service', $editingApiRoute->service ?? 'all') === $serviceValue ? 'selected' : '' }}>{{ $serviceLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">Code</span></label>
                                                    <select name="code" class="select select-bordered" required>
                                                        @foreach($routeCodeOptions as $codeValue => $codeLabel)
                                                        <option value="{{ $codeValue }}" {{ old('code', $editingApiRoute->code ?? 'all') === $codeValue ? 'selected' : '' }}>{{ $codeLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">Priority</span></label>
                                                    <input type="number" min="1" name="priority" class="input input-bordered" value="{{ old('priority', $editingApiRoute->priority ?? 1) }}" placeholder="1" required />
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text">Prefix</span></label>
                                                    <input type="text" name="prefix" class="input input-bordered" value="{{ old('prefix', $editingApiRoute->prefix ?? '') }}" placeholder="017" />
                                                </div>
                                            </div>
                                            <div class="rounded-2xl bg-base-200 p-4">
                                                <input type="hidden" name="status" value="deactive" />
                                                <label class="flex items-center justify-between gap-4">
                                                    <div><span class="font-medium block">Active / Deactive</span><span class="text-xs opacity-70">Active hole route use kora jabe, deactive hole ei rule off thakbe.</span></div>
                                                    <input type="checkbox" name="status" value="active" class="toggle toggle-success toggle-lg" {{ old('status', $editingApiRoute->status ?? 'active') === 'active' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                @if($isEditingRoute)
                                                <a href="{{ route('api.routes.index', array_filter(['connection' => $selectedConnection->id ?? null])) }}#api-route-form" class="btn btn-outline">Back</a>
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
                                        <th>Routing Title</th>
                                        <th>Module</th>
                                        <th>Service</th>
                                        <th>Code</th>
                                        <th>Priority</th>
                                        <th>Prefix</th>
                                        <th>Status</th>
                                        <th class="min-w-[12rem]">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($apiRoutes as $index => $apiRoute)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="font-semibold">{{ $apiRoute->title }}</td>
                                        <td>{{ $apiRoute->module_name }}</td>
                                        <td>{{ $routeServiceOptions[$apiRoute->service] ?? $apiRoute->service }}</td>
                                        <td>{{ $routeCodeOptions[$apiRoute->code] ?? $apiRoute->code }}</td>
                                        <td>{{ $apiRoute->priority }}</td>
                                        <td>{{ $apiRoute->prefix ?: '—' }}</td>
                                        <td><span class="badge {{ $apiRoute->status === 'active' ? 'badge-success' : 'badge-error' }}">{{ $apiRoute->status }}</span></td>
                                        <td>
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('api.routes.index', array_filter(['connection' => $selectedConnection->id ?? null, 'edit_route' => $apiRoute->id])) }}#api-route-form" class="btn btn-xs btn-warning">Modify</a>
                                                <form method="POST" action="{{ route('api.routes.destroy', $apiRoute) }}" onsubmit="return confirm('Delete this route?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="context_connection_id" value="{{ $selectedConnection->id ?? '' }}" />
                                                    <button type="submit" class="btn btn-xs btn-error text-white">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="9" class="text-center text-base-content/60">No route added yet.</td></tr>
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
                    <li><form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">Logout</button></form></li>
                </ul>
            </aside>
        </div>
    </div>
</body>

</html>