<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Admin Dashboard' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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

<body class="min-h-screen bg-base-200 text-base-content transition-colors duration-300">
    @php
    $canManageResellers = auth()->user()?->hasPermission('manage_resellers');
    $resellerPermissionOptions = $resellerPermissionOptions ?? \App\Models\User::resellerPermissionOptions();
    $operatorSales = $operatorSales ?? collect();
    $bankingSales = $bankingSales ?? collect();
    @endphp
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost drawer-button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Admin</a></div>
                <div class="flex-none flex items-center gap-2">
                    @include('partials.theme-toggle')
                    <button class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                    <div class="dropdown dropdown-end">
                        @if(Auth::user() && Auth::user()->profile_picture)
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full">
                                <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="Profile" class="w-full h-full object-cover rounded-full">
                            </div>
                        </div>
                        @else
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                        </div>
                        @endif
                        <ul tabindex="0" class="mt-3 z-1 p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            <li><a href="{{ route('admin.profile') }}">Profile</a></li>
                            <li><a href="{{ route('admin.homepage.edit') }}">Settings</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full text-left">Logout</button></form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="p-6">
                @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
                @endif
                @if(session('error'))
                <div class="alert alert-error mb-4">
                    <span>{{ session('error') }}</span>
                </div>
                @endif
                @if($errors->any())
                <div class="alert alert-error mb-4">
                    <div>
                        <div class="font-semibold mb-1">Please check the following:</div>
                        <ul class="list-disc pl-5 text-sm">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                @hasSection('content')
                @yield('content')
                @else
                @if(isset($driveData))
                <div class="p-6">
                    <h1 class="text-3xl font-bold mb-6">Drive Offer Management</h1>
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Operator</th>
                                            <th>Opcode</th>
                                            <th>Active Drive</th>
                                            <th>Deactive Drive</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $totalActive = 0;
                                        $totalDeactive = 0;
                                        @endphp
                                        @foreach($driveData as $index => $drive)
                                        @php
                                        $totalActive += $drive['active'];
                                        $totalDeactive += $drive['deactive'];
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><a href="{{ route('admin.manage.drive.package', ['operator' => $drive['operator']]) }}" class="font-semibold link link-hover">{{ $drive['operator'] }}</a></td>
                                            <td><span class="badge badge-primary">{{ $drive['opcode'] }}</span></td>
                                            <td><span class="badge badge-success">{{ $drive['active'] }}</span></td>
                                            <td><span class="badge badge-error">{{ $drive['deactive'] }}</span></td>
                                        </tr>
                                        @endforeach
                                        <tr class="font-bold bg-base-200">
                                            <td colspan="3" class="text-left">Total</td>
                                            <td><span class="badge badge-info badge-lg">{{ $totalActive }}</span></td>
                                            <td><span class="badge badge-warning badge-lg">{{ $totalDeactive }}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @elseif(isset($regularData))
                <div class="p-6">
                    <h1 class="text-3xl font-bold mb-6">Regular Offer Management</h1>
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Operator</th>
                                            <th>Opcode</th>
                                            <th>Active Regular</th>
                                            <th>Deactive Regular</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $totalActive = 0;
                                        $totalDeactive = 0;
                                        @endphp
                                        @foreach($regularData as $index => $regular)
                                        @php
                                        $totalActive += $regular['active'];
                                        $totalDeactive += $regular['deactive'];
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><a href="{{ route('admin.manage.regular.package', ['operator' => $regular['operator']]) }}" class="font-semibold link link-hover">{{ $regular['operator'] }}</a></td>
                                            <td><span class="badge badge-primary">{{ $regular['opcode'] }}</span></td>
                                            <td><span class="badge badge-success">{{ $regular['active'] }}</span></td>
                                            <td><span class="badge badge-error">{{ $regular['deactive'] }}</span></td>
                                        </tr>
                                        @endforeach
                                        <tr class="font-bold bg-base-200">
                                            <td colspan="3" class="text-left">Total</td>
                                            <td><span class="badge badge-info badge-lg">{{ $totalActive }}</span></td>
                                            <td><span class="badge badge-warning badge-lg">{{ $totalDeactive }}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @elseif(isset($serviceModules))
                @php
                $isEditingServiceModule = !empty($editingServiceModule);
                $serviceModuleTableReady = $serviceModuleSchemaReady ?? true;
                @endphp
                <div class="p-6 space-y-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Service Modules</h1>
                            <p class="text-sm text-base-content/70">Administration panel module limits, validation rules, and edit controls.</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="stats shadow bg-base-100">
                                <div class="stat py-4 px-5">
                                    <div class="stat-title text-xs">Total Modules</div>
                                    <div class="stat-value text-primary text-2xl">{{ $serviceModules->count() }}</div>
                                </div>
                                <div class="stat py-4 px-5 border-l border-base-200">
                                    <div class="stat-title text-xs">Active</div>
                                    <div class="stat-value text-success text-2xl">{{ $serviceModules->where('status', 'active')->count() }}</div>
                                </div>
                                <div class="stat py-4 px-5 border-l border-base-200">
                                    <div class="stat-title text-xs">Deactive</div>
                                    <div class="stat-value text-error text-2xl">{{ $serviceModules->where('status', 'deactive')->count() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @unless($serviceModuleTableReady)
                    <div class="alert alert-warning">
                        <span>Service Modules table ready noy. Full CRUD feature use korte <code>php artisan migrate</code> run korun.</span>
                    </div>
                    @endunless

                    @if($serviceModuleTableReady)
                    <div id="service-module-form" class="card bg-base-100 shadow-xl">
                        <div class="card-body space-y-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="card-title text-2xl">{{ $isEditingServiceModule ? 'Edit Service Module' : 'Add Service Module' }}</h2>
                                    <p class="text-sm text-base-content/70">Title, limits, required fields, status, ar sort order update korte parben.</p>
                                </div>
                                @if($isEditingServiceModule)
                                <a href="{{ route('admin.service.modules') }}#service-module-form" class="btn btn-sm btn-ghost">Cancel Edit</a>
                                @endif
                            </div>

                            <form method="POST" action="{{ $isEditingServiceModule ? route('admin.service.modules.update', $editingServiceModule->id) : route('admin.service.modules.store') }}" class="space-y-4">
                                @csrf
                                @if($isEditingServiceModule)
                                @method('PUT')
                                @endif

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div class="form-control xl:col-span-2">
                                        <label class="label"><span class="label-text">Title</span></label>
                                        <input type="text" name="title" class="input input-bordered" value="{{ old('title', $editingServiceModule->title ?? '') }}" placeholder="Flexiload" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Minimum Amount</span></label>
                                        <input type="number" step="0.01" min="0" name="minimum_amount" class="input input-bordered" value="{{ old('minimum_amount', $editingServiceModule->minimum_amount ?? '') }}" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Maximum Amount</span></label>
                                        <input type="number" step="0.01" min="0" name="maximum_amount" class="input input-bordered" value="{{ old('maximum_amount', $editingServiceModule->maximum_amount ?? '') }}" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Minimum Length</span></label>
                                        <input type="number" min="1" name="minimum_length" class="input input-bordered" value="{{ old('minimum_length', $editingServiceModule->minimum_length ?? '') }}" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Maximum Length</span></label>
                                        <input type="number" min="1" name="maximum_length" class="input input-bordered" value="{{ old('maximum_length', $editingServiceModule->maximum_length ?? '') }}" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Auto Send Limit</span></label>
                                        <input type="number" step="0.01" min="0" name="auto_send_limit" class="input input-bordered" value="{{ old('auto_send_limit', $editingServiceModule->auto_send_limit ?? '') }}" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Sort Order</span></label>
                                        <input type="number" min="0" name="sort_order" class="input input-bordered" value="{{ old('sort_order', $editingServiceModule->sort_order ?? 0) }}" required />
                                    </div>
                                    <div class="form-control md:col-span-2 xl:col-span-4">
                                        <label class="label"><span class="label-text">Require Pin/Name/NID/Sender</span></label>
                                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 rounded-2xl border border-base-300 p-4">
                                            <label class="label cursor-pointer justify-start gap-3"><input type="checkbox" name="require_pin" value="1" class="checkbox checkbox-sm" @checked((bool) old('require_pin', $editingServiceModule->require_pin ?? false)) /><span class="label-text">Require Pin</span></label>
                                            <label class="label cursor-pointer justify-start gap-3"><input type="checkbox" name="require_name" value="1" class="checkbox checkbox-sm" @checked((bool) old('require_name', $editingServiceModule->require_name ?? false)) /><span class="label-text">Require Name</span></label>
                                            <label class="label cursor-pointer justify-start gap-3"><input type="checkbox" name="require_nid" value="1" class="checkbox checkbox-sm" @checked((bool) old('require_nid', $editingServiceModule->require_nid ?? false)) /><span class="label-text">Require NID</span></label>
                                            <label class="label cursor-pointer justify-start gap-3"><input type="checkbox" name="require_sender" value="1" class="checkbox checkbox-sm" @checked((bool) old('require_sender', $editingServiceModule->require_sender ?? false)) /><span class="label-text">Require Sender</span></label>
                                        </div>
                                    </div>
                                    <div class="form-control md:col-span-2 xl:col-span-4">
                                        <label class="label"><span class="label-text">Status</span></label>
                                        <select name="status" class="select select-bordered w-full md:max-w-xs">
                                            <option value="active" {{ old('status', $editingServiceModule->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="deactive" {{ old('status', $editingServiceModule->status ?? 'active') === 'deactive' ? 'selected' : '' }}>Deactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                    @if($isEditingServiceModule)
                                    <a href="{{ route('admin.service.modules') }}#service-module-form" class="btn btn-outline">Back</a>
                                    @endif
                                    <button type="submit" class="btn btn-primary">{{ $isEditingServiceModule ? 'Save Changes' : 'Create Module' }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Minimum Amount</th>
                                            <th>Maximum Amount</th>
                                            <th>Minimum Length</th>
                                            <th>Maximum Length</th>
                                            <th>Auto Send Limit</th>
                                            <th>Require Pin/Name/NID/Sender</th>
                                            <th>Sort Order</th>
                                            <th>Status</th>
                                            <th class="text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($serviceModules as $module)
                                        <tr>
                                            <td>
                                                <div class="font-semibold">{{ $module['title'] }}</div>
                                            </td>
                                            <td>৳{{ number_format($module['minimum_amount'], 2) }}</td>
                                            <td>৳{{ number_format($module['maximum_amount'], 2) }}</td>
                                            <td>{{ $module['minimum_length'] }}</td>
                                            <td>{{ $module['maximum_length'] }}</td>
                                            <td>৳{{ number_format($module['auto_send_limit'], 2) }}</td>
                                            <td>
                                                <div class="flex flex-wrap gap-1 max-w-xs">
                                                    @foreach($module['requirements'] as $label => $enabled)
                                                    <span class="badge {{ $enabled ? 'badge-success' : 'badge-ghost' }} badge-sm">{{ $label }}: {{ $enabled ? 'Yes' : 'No' }}</span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-outline">{{ $module['sort_order'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $module['status'] === 'active' ? 'badge-success' : 'badge-error' }}">{{ ucfirst($module['status']) }}</span>
                                            </td>
                                            <td>
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    @if($serviceModuleTableReady && !empty($module['id']))
                                                    <a href="{{ route('admin.service.modules', ['edit' => $module['id']]) }}#service-module-form" class="btn btn-primary btn-xs">Edit</a>
                                                    @else
                                                    <span class="text-xs text-base-content/60">Read only</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-base-content/60">No service module saved yet.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @elseif(isset($rechargeBlockLists))
                @php
                $rechargeBlockTableReady = $rechargeBlockListSchemaReady ?? true;
                @endphp
                <div class="p-6 space-y-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Block list</h1>
                            <p class="text-sm text-base-content/70">Blocked recharge amounts add korle oi service/operator amount ar request kora jabe na.</p>
                        </div>
                        <div class="stats shadow bg-base-100">
                            <div class="stat py-4 px-5">
                                <div class="stat-title text-xs">Total Blocked</div>
                                <div class="stat-value text-primary text-2xl">{{ $rechargeBlockLists->count() }}</div>
                            </div>
                            <div class="stat py-4 px-5 border-l border-base-200">
                                <div class="stat-title text-xs">Flexiload</div>
                                <div class="stat-value text-secondary text-2xl">{{ $rechargeBlockLists->where('service', 'Flexiload')->count() }}</div>
                            </div>
                            <div class="stat py-4 px-5 border-l border-base-200">
                                <div class="stat-title text-xs">InternetPack</div>
                                <div class="stat-value text-accent text-2xl">{{ $rechargeBlockLists->where('service', 'InternetPack')->count() }}</div>
                            </div>
                        </div>
                    </div>

                    @unless($rechargeBlockTableReady)
                    <div class="alert alert-warning">
                        <span>Recharge Block List table ready noy. Full feature use korte <code>php artisan migrate</code> run korun.</span>
                    </div>
                    @endunless

                    @if($rechargeBlockTableReady)
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body space-y-4">
                            <div>
                                <h2 class="card-title text-2xl">Add block item</h2>
                                <p class="text-sm text-base-content/70">Service, operator ar amount select kore blocked list-e add korun.</p>
                            </div>

                            <form method="POST" action="{{ route('admin.recharge.block.list.store') }}" class="grid gap-4 md:grid-cols-4">
                                @csrf
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Service</span></label>
                                    <select name="service" class="select select-bordered" required>
                                        <option value="" disabled {{ old('service') ? '' : 'selected' }}>Select service</option>
                                        @foreach($rechargeBlockServiceOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('service') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">operator</span></label>
                                    <select name="operator" class="select select-bordered" required>
                                        <option value="" disabled {{ old('operator') ? '' : 'selected' }}>Select operator</option>
                                        @foreach($rechargeBlockOperatorOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('operator') === $value ? 'selected' : '' }}>{{ $value }} - {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Amount</span></label>
                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="input input-bordered" placeholder="298" required />
                                </div>
                                <div class="form-control justify-end">
                                    <label class="label opacity-0"><span class="label-text">Action</span></label>
                                    <button type="submit" class="btn btn-primary w-full">Add</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th>Nr.</th>
                                            <th>Service</th>
                                            <th>operator</th>
                                            <th>Amount</th>
                                            <th class="text-right">Operation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($rechargeBlockLists as $blockItem)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><span class="font-semibold">{{ $blockItem->service }}</span></td>
                                            <td><span class="badge badge-outline">{{ $blockItem->operator }}</span></td>
                                            <td>৳{{ number_format((float) $blockItem->amount, 2) }}</td>
                                            <td>
                                                <div class="flex justify-end">
                                                    @if($rechargeBlockTableReady)
                                                    <form method="POST" action="{{ route('admin.recharge.block.list.destroy', $blockItem->id) }}" onsubmit="return confirm('Delete this blocked recharge amount?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-error btn-xs">Delete</button>
                                                    </form>
                                                    @else
                                                    <span class="text-xs text-base-content/60">Read only</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-base-content/60">No blocked recharge amount added yet.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @elseif(isset($securitySettings))
                @php
                $securitySettingsTableReady = $securitySettingsSchemaReady ?? true;
                $operatorFields = [
                'security_gp',
                'security_robi',
                'security_banglalink',
                'security_airtel',
                'security_teletalk',
                'security_skitto',
                ];
                $operatorsOffCount = collect($operatorFields)->filter(fn ($field) => ($securitySettings[$field] ?? 'off') === 'off')->count();
                $balanceToolsOnCount = collect([
                'security_bank_balance',
                'security_drive_balance',
                'security_balance_transfer',
                ])->filter(fn ($field) => ($securitySettings[$field] ?? 'off') === 'on')->count();
                $securitySections = [
                [
                'title' => 'Access & Login',
                'description' => 'SSL redirect, capcha, Google reCAPTCHA, password ar session related controls.',
                'fields' => [
                ['name' => 'security_recaptcha', 'label' => 'Google reCAPTCHA', 'type' => 'select', 'options' => $securitySettingOptions['enable_disable']],
                ['name' => 'security_ssl_https_redirect', 'label' => 'SSL/HTTPS Redirect', 'type' => 'select', 'options' => $securitySettingOptions['enable_disable']],
                ['name' => 'security_admin_login_captcha', 'label' => 'Admin login capcha', 'type' => 'select', 'options' => $securitySettingOptions['enable_disable']],
                ['name' => 'security_reseller_login_captcha', 'label' => 'Reseller login capcha', 'type' => 'select', 'options' => $securitySettingOptions['enable_disable']],
                ['name' => 'security_pin_expire_days', 'label' => 'PIN Expire (days)', 'type' => 'number', 'min' => 0],
                ['name' => 'security_password_expire_days', 'label' => 'Password Expire (days)', 'type' => 'number', 'min' => 0],
                ['name' => 'security_password_strong', 'label' => 'Password Strong', 'type' => 'select', 'options' => $securitySettingOptions['yes_no']],
                ['name' => 'security_minimum_pin_length', 'label' => 'Minimum PIN Length', 'type' => 'number', 'min' => 1],
                ['name' => 'security_request_interval_minutes', 'label' => 'Request Interval (Minutes)', 'type' => 'number', 'min' => 0],
                ['name' => 'security_session_timeout_minutes', 'label' => 'Session Time Logout (Minutes)', 'type' => 'number', 'min' => 1],
                ],
                ],
                [
                'title' => 'Messaging & Limits',
                'description' => 'Support ticket, OTP channel, modem ar limit settings.',
                'fields' => [
                ['name' => 'security_support_ticket', 'label' => 'Enable support ticket', 'type' => 'select', 'options' => $securitySettingOptions['enable_disable']],
                ['name' => 'security_send_otp_via', 'label' => 'Send OTP Via', 'type' => 'select', 'options' => $securitySettingOptions['delivery_channels']],
                ['name' => 'security_send_alert_via', 'label' => 'Send Alert Via', 'type' => 'select', 'options' => $securitySettingOptions['delivery_channels']],
                ['name' => 'security_send_offline_sms_via', 'label' => 'Send offline sms Via', 'type' => 'select', 'options' => $securitySettingOptions['delivery_channels']],
                ['name' => 'security_bulk_flexi_limit', 'label' => 'Bulk Flexi Limit', 'type' => 'number', 'min' => 0],
                ['name' => 'security_auto_sending_limit', 'label' => 'Auto Sending Limit', 'type' => 'number', 'min' => 0],
                ['name' => 'security_reseller_overpayment_limit', 'label' => 'Reseller OverPayment Limit', 'type' => 'select', 'options' => $securitySettingOptions['yes_no']],
                ['name' => 'security_modem', 'label' => 'Modem', 'type' => 'select', 'options' => $securitySettingOptions['modems']],
                ['name' => 'security_daily_limit', 'label' => 'Dayli Limit', 'type' => 'number', 'min' => 0],
                ],
                ],
                [
                'title' => 'Operator & Balance Control',
                'description' => 'Operator off/on, popup notice, balance tools ar commission settings.',
                'fields' => [
                ['name' => 'security_gp', 'label' => 'GP', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_robi', 'label' => 'ROBI', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_banglalink', 'label' => 'Banglalink', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_airtel', 'label' => 'Airtel', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_teletalk', 'label' => 'Teletalk', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_skitto', 'label' => 'Skitto', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_popup_notice', 'label' => 'Popup Notice', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_sms_sent_system', 'label' => 'Sms Sent System', 'type' => 'select', 'options' => $securitySettingOptions['sms_sent_systems']],
                ['name' => 'security_bank_balance', 'label' => 'Bank Balance', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_drive_balance', 'label' => 'Drive Balance', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_balance_transfer', 'label' => 'Balance Transfer', 'type' => 'select', 'options' => $securitySettingOptions['on_off']],
                ['name' => 'security_commission_system', 'label' => 'Comission system', 'type' => 'select', 'options' => $securitySettingOptions['commission_systems']],
                ],
                ],
                ];
                @endphp
                <div class="p-6 space-y-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold">Security Modual</h1>
                            <p class="text-sm text-base-content/70">Login, messaging, operator ar balance related security controls ekhan theke manage korte parben.</p>
                        </div>
                        <div class="stats shadow bg-base-100">
                            <div class="stat py-4 px-5">
                                <div class="stat-title text-xs">Configured Options</div>
                                <div class="stat-value text-primary text-2xl">{{ count($securitySettings) }}</div>
                            </div>
                            <div class="stat py-4 px-5 border-l border-base-200">
                                <div class="stat-title text-xs">Operators OFF</div>
                                <div class="stat-value text-warning text-2xl">{{ $operatorsOffCount }}</div>
                            </div>
                            <div class="stat py-4 px-5 border-l border-base-200">
                                <div class="stat-title text-xs">Balance Tools ON</div>
                                <div class="stat-value text-success text-2xl">{{ $balanceToolsOnCount }}</div>
                            </div>
                        </div>
                    </div>

                    @unless($securitySettingsTableReady)
                    <div class="alert alert-warning">
                        <span>Security Modual settings columns ready noy. Full save feature use korte <code>php artisan migrate</code> run korun.</span>
                    </div>
                    @endunless

                    <form method="POST" action="{{ route('admin.security.modual.update') }}" class="space-y-6">
                        @csrf
                        <fieldset class="space-y-6" {{ $securitySettingsTableReady ? '' : 'disabled' }}>
                            <div class="space-y-6 max-w-6xl">
                                @foreach($securitySections as $section)
                                <div class="card bg-base-100 shadow-xl">
                                    <div class="card-body space-y-4">
                                        <div>
                                            <h2 class="card-title text-2xl">{{ $section['title'] }}</h2>
                                            <p class="text-sm text-base-content/70">{{ $section['description'] }}</p>
                                        </div>
                                        <div class="grid gap-4 md:grid-cols-2">
                                            @foreach($section['fields'] as $field)
                                            <div class="form-control">
                                                <label class="label"><span class="label-text">{{ $field['label'] }}</span></label>
                                                @if($field['type'] === 'select')
                                                <select name="{{ $field['name'] }}" class="select select-bordered w-full" required>
                                                    @foreach($field['options'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" {{ old($field['name'], $securitySettings[$field['name']] ?? null) === $optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                                @else
                                                <input type="number" name="{{ $field['name'] }}" min="{{ $field['min'] ?? 0 }}" class="input input-bordered w-full" value="{{ old($field['name'], $securitySettings[$field['name']] ?? '') }}" required />
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="btn btn-primary btn-wide">Save Changes</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
                @elseif(isset($history))
                <div class="p-6">
                    <h1 class="text-3xl font-bold mb-6">Drive History</h1>
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Operator</th>
                                            <th>Mobile</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($history as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td>{{ $item->user->name ?? 'N/A' }}</td>
                                            <td><span class="badge badge-primary">{{ $item->operator }}</span></td>
                                            <td>{{ $item->mobile }}</td>
                                            <td>৳{{ number_format($item->amount, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $item->status == 'success' ? 'badge-success' : 'badge-error' }}">
                                                    {{ ucfirst($item->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $item->description ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No history found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @elseif(isset($allHistory))
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">All History</h1>
                    </div>

                    <!-- Filter Section -->
                    <div class="card bg-base-100 shadow-md mb-6 p-4">
                        <form method="GET" action="{{ route('admin.all.history') }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Show</span></label>
                                    <select name="show" class="select select-bordered select-sm">
                                        <option value="50" {{ ($show ?? 50) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="25" {{ ($show ?? 50) == 25 ? 'selected' : '' }}>25</option>
                                        <option value="100" {{ ($show ?? 50) == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Number</span></label>
                                    <input type="text" name="number" value="{{ $number ?? '' }}" placeholder="Search number..." class="input input-bordered input-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Reseller</span></label>
                                    <input type="text" name="reseller" value="{{ $reseller ?? '' }}" placeholder="Search reseller..." class="input input-bordered input-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Services</span></label>
                                    <select name="service" class="select select-bordered select-sm">
                                        <option value="">--Any--</option>
                                        <option value="drive" {{ ($service ?? '') == 'drive' ? 'selected' : '' }}>Drive</option>
                                        <option value="bkash" {{ ($service ?? '') == 'bkash' ? 'selected' : '' }}>Bkash</option>
                                        <option value="nagad" {{ ($service ?? '') == 'nagad' ? 'selected' : '' }}>Nagad</option>
                                        <option value="rocket" {{ ($service ?? '') == 'rocket' ? 'selected' : '' }}>Rocket</option>
                                        <option value="upay" {{ ($service ?? '') == 'upay' ? 'selected' : '' }}>Upay</option>
                                        <option value="flexi" {{ ($service ?? '') == 'flexi' ? 'selected' : '' }}>Flexi</option>
                                        <option value="internet" {{ ($service ?? '') == 'internet' ? 'selected' : '' }}>Internet</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Status</span></label>
                                    <select name="status" class="select select-bordered select-sm">
                                        <option value="">--Any--</option>
                                        <option value="success" {{ ($status ?? '') == 'success' ? 'selected' : '' }}>Success</option>
                                        <option value="failed" {{ ($status ?? '') == 'failed' ? 'selected' : '' }}>Failed</option>
                                        <option value="pending" {{ ($status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="cancelled" {{ ($status ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Date From</span></label>
                                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="input input-bordered input-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Date To</span></label>
                                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="input input-bordered input-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">&nbsp;</span></label>
                                    <button type="submit" class="btn btn-primary btn-sm bg-blue-500 hover:bg-blue-600 border-0 text-white">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body p-0">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>User</th>
                                            <th>Service</th>
                                            <th>Operator</th>
                                            <th>Mobile</th>
                                            <th>Amount</th>
                                            <th>Cost</th>
                                            <th>Status</th>
                                            <th>Balance</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($allHistory as $index => $item)
                                        <tr>
                                            <td>{{ $allHistory->count() - $index }}</td>
                                            <td>{{ $item->user->name ?? 'N/A' }}</td>
                                            <td>
                                                @if($item->service === 'drive')
                                                <span class="badge badge-info">Drive</span>
                                                @elseif($item->service === 'mobile_banking')
                                                <span class="badge badge-warning">Mobile Banking</span>
                                                @elseif($item->service === 'flexi')
                                                <span class="badge badge-secondary">Flexi</span>
                                                @else
                                                <span class="badge badge-primary">Internet</span>
                                                @endif
                                            </td>
                                            <td><span class="badge badge-primary">{{ $item->operator }}</span></td>
                                            <td>{{ $item->mobile ?? '-' }}</td>
                                            <td>৳{{ number_format($item->amount, 2) }}</td>
                                            <td>৳{{ number_format($item->cost ?? 0, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $item->status == 'success' ? 'badge-success' : ($item->status == 'failed' ? 'badge-error' : 'badge-warning') }}">
                                                    {{ ucfirst($item->status) }}
                                                </span>
                                            </td>
                                            <td>৳{{ number_format($item->balance ?? 0, 2) }}</td>
                                            <td>{{ $item->description ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="11" class="text-center">No history found</td>
                                        </tr>
                                        @endforelse
                                        @if($allHistory->count() > 0)
                                        <tr class="font-bold bg-base-200">
                                            <td colspan="5" class="text-right">Total:</td>
                                            <td><span class="badge badge-success badge-lg">৳{{ number_format($totalAmount ?? 0, 2) }}</span></td>
                                            <td><span class="badge badge-info badge-lg">৳{{ number_format($totalCost ?? 0, 2) }}</span></td>
                                            <td colspan="3"></td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @elseif(isset($internetHistory))
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Internet Pack History</h1>
                        <form method="GET" action="{{ route('admin.internet.history') }}" class="flex gap-2">
                            <select name="date" class="select select-bordered select-sm" onchange="this.form.submit()">
                                <option value="today" {{ ($dateFilter ?? 'today') == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ ($dateFilter ?? 'today') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="week" {{ ($dateFilter ?? 'today') == 'week' ? 'selected' : '' }}>This Week</option>
                                <option value="month" {{ ($dateFilter ?? 'today') == 'month' ? 'selected' : '' }}>This Month</option>
                            </select>
                        </form>
                    </div>
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body p-0">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>User</th>
                                            <th>Operator</th>
                                            <th>Mobile</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($internetHistory as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->user->name ?? 'N/A' }}</td>
                                            <td><span class="badge badge-primary">{{ $item->operator }}</span></td>
                                            <td>{{ $item->mobile ?? '-' }}</td>
                                            <td>৳{{ number_format($item->amount, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $item->status == 'success' ? 'badge-success' : ($item->status == 'failed' ? 'badge-error' : 'badge-warning') }}">
                                                    {{ ucfirst($item->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $item->description ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No internet pack history found</td>
                                        </tr>
                                        @endforelse
                                        @if($internetHistory->count() > 0)
                                        <tr class="font-bold bg-base-200">
                                            <td colspan="4" class="text-right">Total:</td>
                                            <td><span class="badge badge-success badge-lg">৳{{ number_format($totalAmount ?? 0, 2) }}</span></td>
                                            <td colspan="3"></td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @elseif(isset($requests))
                <div class="p-6">
                    <h1 class="text-3xl font-bold mb-6">Pending Requests</h1>

                    <div class="card bg-base-100 shadow-md mb-6 p-4">
                        <form method="GET" action="{{ route('admin.pending.drive.requests') }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Show</span></label>
                                    <select name="show" class="select select-bordered select-sm">
                                        <option value="50" {{ ($show ?? 50) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="25" {{ ($show ?? 50) == 25 ? 'selected' : '' }}>25</option>
                                        <option value="100" {{ ($show ?? 50) == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Number</span></label>
                                    <input type="text" name="number" value="{{ $number ?? '' }}" placeholder="Number" class="input input-bordered input-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Reseller</span></label>
                                    <input type="text" name="reseller" value="{{ $reseller ?? '' }}" placeholder="Reseller" class="input input-bordered input-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Services</span></label>
                                    <select name="service" class="select select-bordered select-sm">
                                        <option value="">--Any--</option>
                                        <option value="drive" {{ ($service ?? '') == 'drive' ? 'selected' : '' }}>Drive</option>
                                        <option value="internet" {{ ($service ?? '') == 'internet' ? 'selected' : '' }}>Internet</option>
                                        <option value="flexi" {{ ($service ?? '') == 'flexi' ? 'selected' : '' }}>Flexi</option>
                                        <option value="bkash" {{ ($service ?? '') == 'bkash' ? 'selected' : '' }}>Bkash</option>
                                        <option value="nagad" {{ ($service ?? '') == 'nagad' ? 'selected' : '' }}>Nagad</option>
                                        <option value="rocket" {{ ($service ?? '') == 'rocket' ? 'selected' : '' }}>Rocket</option>
                                        <option value="upay" {{ ($service ?? '') == 'upay' ? 'selected' : '' }}>Upay</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Status</span></label>
                                    <select name="status" class="select select-bordered select-sm">
                                        <option value="">--Any--</option>
                                        <option value="pending" {{ ($status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="waiting" {{ ($status ?? '') == 'waiting' ? 'selected' : '' }}>Waiting</option>
                                        <option value="process" {{ ($status ?? '') == 'process' ? 'selected' : '' }}>Process</option>
                                        <option value="resend" {{ ($status ?? '') == 'resend' ? 'selected' : '' }}>Resend</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Date From</span></label>
                                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="input input-bordered input-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Date To</span></label>
                                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="input input-bordered input-sm" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">&nbsp;</span></label>
                                    <div class="flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm bg-blue-500 hover:bg-blue-600 border-0 text-white">Filter</button>
                                        <button type="button" onclick="window.print()" class="btn btn-outline btn-sm">Print</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            @php
                            $selectedRequestKeys = old('request_keys', []);
                            $hasBulkSelectableRequests = $requests->contains(function ($item) {
                            $requestType = strtolower((string) ($item->request_type ?? 'drive'));
                            $isManualPaymentRequest = ($item->request_category ?? '') === 'manual_payment';

                            return $requestType !== 'flexi' && ! $isManualPaymentRequest;
                            });
                            @endphp
                            <div class="overflow-x-auto">
                                <table class="table table-zebra">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="flex items-center gap-2">
                                                    <span>Select</span>
                                                    @if($hasBulkSelectableRequests)
                                                    <input type="checkbox" id="pending-bulk-select-all" class="checkbox checkbox-sm" />
                                                    @endif
                                                </div>
                                            </th>
                                            <th>Sl</th>
                                            <th>User</th>
                                            <th>Operator</th>
                                            <th>Type</th>
                                            <th>Package</th>
                                            <th>Mobile</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($requests as $index => $request)
                                        @php
                                        $requestType = $request->request_type ?? 'Drive';
                                        $requestKey = strtolower($requestType) . ':' . $request->id;
                                        $isFlexiRequest = strtolower($requestType) === 'flexi';
                                        $isManualPaymentRequest = ($request->request_category ?? '') === 'manual_payment';
                                        @endphp
                                        <tr>
                                            <td>
                                                @if(!$isFlexiRequest && !$isManualPaymentRequest)
                                                <input
                                                    type="checkbox"
                                                    class="checkbox checkbox-sm pending-bulk-checkbox"
                                                    form="pending-bulk-action-form"
                                                    name="request_keys[]"
                                                    value="{{ $requestKey }}"
                                                    {{ in_array($requestKey, $selectedRequestKeys, true) ? 'checked' : '' }} />
                                                @else
                                                <span class="text-xs text-base-content/50">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $requests->count() - $index }}</td>
                                            <td>{{ $request->user->name }}</td>
                                            <td><span class="badge badge-primary">{{ $request->operator }}</span></td>
                                            <td><span class="badge badge-info">{{ $requestType }}</span></td>
                                            <td>{{ ($isFlexiRequest || $isManualPaymentRequest) ? ($request->type ?? 'N/A') : ($request->package->name ?? 'N/A') }}</td>
                                            <td>{{ $request->mobile }}</td>
                                            <td>৳{{ number_format($request->amount, 2) }}</td>
                                            @php
                                            $displayStatus = $request->display_status ?? $request->status;
                                            @endphp
                                            <td>
                                                <span class="badge {{ $displayStatus === 'process' ? 'badge-info' : ($displayStatus === 'waiting' ? 'badge-warning' : ($displayStatus === 'resend' ? 'badge-secondary' : 'badge-warning')) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $displayStatus)) }}
                                                </span>
                                            </td>
                                            <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                                            <td>
                                                @if($requestType === 'Drive')
                                                <div class="flex gap-2">
                                                    <form method="POST" action="/admin/drive-requests/{{ $request->id }}/approve" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm">Success</button>
                                                    </form>
                                                    <form method="POST" action="/admin/drive-requests/{{ $request->id }}/failed" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-error btn-sm">Failed</button>
                                                    </form>
                                                    <form method="POST" action="/admin/drive-requests/{{ $request->id }}/cancel" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-warning btn-sm">Cancel</button>
                                                    </form>
                                                </div>
                                                @elseif($requestType === 'Internet')
                                                <div class="flex gap-2">
                                                    <form method="POST" action="/admin/regular-requests/{{ $request->id }}/approve" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm">Success</button>
                                                    </form>
                                                    <form method="POST" action="/admin/regular-requests/{{ $request->id }}/failed" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-error btn-sm">Failed</button>
                                                    </form>
                                                    <form method="POST" action="/admin/regular-requests/{{ $request->id }}/cancel" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-warning btn-sm">Cancel</button>
                                                    </form>
                                                </div>
                                                @elseif($requestType === 'Flexi')
                                                <div class="flex gap-2">
                                                    <form method="POST" action="/admin/flexi-requests/{{ $request->id }}/approve" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm">Success</button>
                                                    </form>
                                                    <form method="POST" action="/admin/flexi-requests/{{ $request->id }}/failed" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-error btn-sm">Failed</button>
                                                    </form>
                                                    <form method="POST" action="/admin/flexi-requests/{{ $request->id }}/cancel" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-warning btn-sm">Cancel</button>
                                                    </form>
                                                </div>
                                                @elseif($isManualPaymentRequest)
                                                <div class="flex gap-2">
                                                    <form method="POST" action="/admin/manual-payment-requests/{{ $request->id }}/approve" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm">Success</button>
                                                    </form>
                                                    <form method="POST" action="/admin/manual-payment-requests/{{ $request->id }}/failed" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-error btn-sm">Failed</button>
                                                    </form>
                                                    <form method="POST" action="/admin/manual-payment-requests/{{ $request->id }}/cancel" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-warning btn-sm">Cancel</button>
                                                    </form>
                                                </div>
                                                @else
                                                <span class="text-sm text-base-content/60">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="11" class="text-center">No pending requests</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($requests->isNotEmpty() && $hasBulkSelectableRequests)
                            <div class="border-t border-base-200 mt-6 pt-6">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Bulk Action</span></label>
                                        <form id="pending-bulk-action-form" method="POST" action="{{ route('admin.pending.requests.bulk-action') }}">
                                            @csrf
                                            <input type="hidden" name="show" value="{{ $show ?? 50 }}" />
                                            <input type="hidden" name="number" value="{{ $number ?? '' }}" />
                                            <input type="hidden" name="reseller" value="{{ $reseller ?? '' }}" />
                                            <input type="hidden" name="service" value="{{ $service ?? '' }}" />
                                            <input type="hidden" name="status" value="{{ $status ?? '' }}" />
                                            <input type="hidden" name="date_from" value="{{ $dateFrom ?? '' }}" />
                                            <input type="hidden" name="date_to" value="{{ $dateTo ?? '' }}" />
                                            <select name="bulk_action" class="select select-bordered select-sm w-full">
                                                <option value="">--Select--</option>
                                                <option value="resend" {{ old('bulk_action') === 'resend' ? 'selected' : '' }}>Resend</option>
                                                <option value="waiting" {{ old('bulk_action') === 'waiting' ? 'selected' : '' }}>Waiting</option>
                                                <option value="manual_complete" {{ old('bulk_action') === 'manual_complete' ? 'selected' : '' }}>Manual Complete</option>
                                                <option value="process" {{ old('bulk_action') === 'process' ? 'selected' : '' }}>Process</option>
                                                <option value="cancel" {{ old('bulk_action') === 'cancel' ? 'selected' : '' }}>Cancel</option>
                                            </select>
                                        </form>
                                    </div>
                                    <div class="form-control md:col-span-2">
                                        <label class="label"><span class="label-text">Description</span></label>
                                        <input type="text" form="pending-bulk-action-form" name="bulk_note" value="{{ old('bulk_note') }}" placeholder="Bulk action text" class="input input-bordered input-sm w-full" />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">PIN</span></label>
                                        <input type="password" form="pending-bulk-action-form" name="pin" placeholder="Admin PIN" class="input input-bordered input-sm w-full" maxlength="4" />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">&nbsp;</span></label>
                                        <button type="submit" form="pending-bulk-action-form" class="btn btn-primary btn-sm">Submit</button>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @elseif(isset($users))
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl font-bold">Reseller All</h1>
                    <button onclick="add_user_modal.showModal()" class="btn btn-primary">Add User</button>
                </div>

                <!-- Filter Section -->
                <div class="card bg-base-100 shadow-md mb-6 p-4">
                    <form method="GET" action="{{ route('admin.resellers') }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Show</span></label>
                                <select class="select select-bordered select-sm">
                                    <option>25</option>
                                    <option>50</option>
                                    <option>100</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">Username</span></label>
                                <input type="text" name="username" value="{{ $username ?? '' }}" placeholder="Search username..." class="input input-bordered input-sm" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">Reseller</span></label>
                                <select name="level" class="select select-bordered select-sm">
                                    <option value="">--All--</option>
                                    <option value="house" {{ ($level ?? '') == 'house' ? 'selected' : '' }}>House</option>
                                    <option value="dgm" {{ ($level ?? '') == 'dgm' ? 'selected' : '' }}>DGM</option>
                                    <option value="dealer" {{ ($level ?? '') == 'dealer' ? 'selected' : '' }}>Dealer</option>
                                    <option value="seller" {{ ($level ?? '') == 'seller' ? 'selected' : '' }}>Seller</option>
                                    <option value="retailer" {{ ($level ?? '') == 'retailer' ? 'selected' : '' }}>Retailer</option>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label"><span class="label-text">Status</span></label>
                                <select name="status" class="select select-bordered select-sm">
                                    <option value="">--Any--</option>
                                    <option value="active" {{ ($status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ ($status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">&nbsp;</span></label>
                                <button type="submit" class="btn btn-primary btn-sm bg-blue-500 hover:bg-blue-600 border-0 text-white mt-6">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card bg-base-100 shadow-md mb-6 p-4">
                    <form id="bulk-reseller-form" method="POST" action="{{ route('admin.resellers.bulk-action') }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Bulk Action</span></label>
                                <select name="action" class="select select-bordered select-sm" required>
                                    <option value="">--Select--</option>
                                    <option value="active">Active</option>
                                    <option value="deactive">Deactive</option>
                                    <option value="delete">Delete</option>
                                    <option value="cancel_otp">Cancel OTP</option>
                                </select>
                            </div>
                            <div class="form-control md:col-span-2">
                                <label class="label"><span class="label-text">&nbsp;</span></label>
                                <button type="submit" class="btn btn-primary btn-sm w-full md:w-auto">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full text-xs">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="bulk-select-all" class="checkbox checkbox-sm" />
                                </th>
                                <th>Id</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Details</th>
                                <th>Main Bal</th>
                                <th>Bank Bal</th>
                                <th>Drive Bal</th>
                                <th>Stock</th>
                                <th>Last Login</th>
                                <th>Level</th>
                                <th>Otp</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Parent</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $u)
                            <tr>
                                <td>
                                    <input form="bulk-reseller-form" type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="checkbox checkbox-sm bulk-user-checkbox" />
                                </td>
                                <td>{{ $u->id }}</td>
                                <td><a href="{{ route('admin.resellers.show', $u) }}" class="link link-primary font-medium">{{ $u->username ?? $u->email }}</a></td>
                                <td><a href="{{ route('admin.resellers.show', $u) }}" class="link link-primary font-medium">{{ $u->name }}</a></td>
                                <td><a href="{{ route('admin.resellers.show', $u) }}" class="btn btn-xs bg-blue-500 hover:bg-blue-600 border-0 text-white">Change</a></td>
                                <td>{{ $u->main_bal ?? '0.00' }}</td>
                                <td>{{ $u->bank_bal ?? '0.00' }}</td>
                                <td>{{ $u->drive_bal ?? '0.00' }}</td>
                                <td>{{ $u->stock ?? '0.00' }}</td>
                                <td>{{ $u->last_login ?? $u->updated_at }}</td>
                                <td><a href="{{ route('admin.resellers', ['level' => $u->level]) }}" class="link link-primary">{{ ucfirst($u->level ?? '-') }}</a></td>
                                <td><span class="badge {{ $u->google_otp_enabled ? 'badge-success' : 'badge-error' }}">{{ $u->google_otp_enabled ? 'On' : 'Off' }}</span></td>
                                <td>{{ $u->created_at }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.resellers.toggle', $u) }}" class="inline">@csrf
                                        <button type="submit" class="px-3 py-1 rounded {{ $u->is_active ? 'bg-blue-500 text-white' : 'bg-red-500 text-white' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</button>
                                    </form>
                                </td>
                                <td>{{ $u->parent ? $u->parent->name : 'Self' }}</td>
                                <td><a href="{{ route('admin.add.balance', $u->id) }}" class="btn btn-xs bg-blue-500 hover:bg-blue-600 border-0 text-white px-3 py-1">Add Balance</a></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="16" class="text-center">No reseller accounts found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @elseif(isset($resellerUser))
                @php
                $selectedPermissions = old('permissions', $resellerUser->permissionKeys());
                @endphp
                <div class="mb-6">
                    <h1 class="text-2xl font-bold">Reseller Details</h1>
                    <p class="text-sm text-base-content/70">Update reseller access, credentials, and level.</p>
                </div>

                <div class="card bg-base-100 shadow-md">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.resellers.update', $resellerUser) }}">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <div class="form-control mb-4">
                                        <label class="label"><span class="label-text">Name</span></label>
                                        <input type="text" name="name" value="{{ old('name', $resellerUser->name) }}" class="input input-bordered w-full" required />
                                    </div>
                                    <div class="form-control mb-4">
                                        <label class="label"><span class="label-text">Email</span></label>
                                        <input type="email" name="email" value="{{ old('email', $resellerUser->email) }}" class="input input-bordered w-full" required />
                                    </div>
                                    <div class="form-control mb-4">
                                        <label class="label"><span class="label-text">Level</span></label>
                                        <select name="level" class="select select-bordered w-full" required>
                                            <option value="house" {{ old('level', $resellerUser->level) == 'house' ? 'selected' : '' }}>House</option>
                                            <option value="dgm" {{ old('level', $resellerUser->level) == 'dgm' ? 'selected' : '' }}>DGM</option>
                                            <option value="dealer" {{ old('level', $resellerUser->level) == 'dealer' ? 'selected' : '' }}>Dealer</option>
                                            <option value="seller" {{ old('level', $resellerUser->level) == 'seller' ? 'selected' : '' }}>Seller</option>
                                            <option value="retailer" {{ old('level', $resellerUser->level) == 'retailer' ? 'selected' : '' }}>Retailer</option>
                                        </select>
                                    </div>
                                    <div class="form-control mb-4">
                                        <label class="label"><span class="label-text">Admin PIN</span></label>
                                        <input type="password" name="admin_pin" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" class="input input-bordered w-full @error('admin_pin') input-error @enderror" placeholder="Enter your 4-digit admin PIN to confirm changes" required />
                                        <label class="label"><span class="label-text-alt text-base-content/70">Required to save reseller updates.</span></label>
                                    </div>
                                    <div class="text-sm text-base-content/70 space-y-1">
                                        <p><span class="font-semibold">Parent:</span> {{ $resellerUser->parent?->name ?? 'Self' }}</p>
                                        <p><span class="font-semibold">Status:</span> {{ $resellerUser->is_active ? 'Active' : 'Inactive' }}</p>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold mb-3">Reseller Permissions</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($resellerPermissionOptions as $permissionKey => $permissionLabel)
                                        <label class="label cursor-pointer justify-start gap-3 bg-base-200 rounded-lg px-3 py-2">
                                            <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" class="checkbox checkbox-primary checkbox-sm"
                                                {{ in_array($permissionKey, $selectedPermissions, true) ? 'checked' : '' }} />
                                            <span class="label-text">{{ $permissionLabel }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-center gap-2">
                                <a href="{{ route('admin.resellers') }}" class="btn">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Reseller</button>
                            </div>
                        </form>
                    </div>
                </div>
                @elseif(isset($deletedUsers))
                <div class="mb-4">
                    <h1 class="text-2xl font-bold">Deleted Accounts</h1>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full text-sm">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Level</th>
                                <th>Deleted At</th>
                                <th>Parent</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deletedUsers as $u)
                            <tr>
                                <td>{{ $u->id }}</td>
                                <td>{{ $u->username ?? $u->email }}</td>
                                <td>{{ $u->name }}</td>
                                <td>{{ ucfirst($u->level ?? '-') }}</td>
                                <td>{{ optional($u->deleted_at)->format('d M Y H:i') }}</td>
                                <td>{{ $u->parent ? $u->parent->name : 'Self' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.deleted.accounts.restore', $u->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Restore</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No deleted accounts found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <p class="text-sm text-base-content/60">Total Amount</p>
                            <h2 class="text-3xl font-bold text-success">৳{{ number_format($totalAmount ?? 0, 2) }}</h2>
                        </div>
                    </div>
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <p class="text-sm text-base-content/60">Total Users</p>
                            <h2 class="text-3xl font-bold text-accent">{{ number_format($totalUsers ?? 0) }}</h2>
                        </div>
                    </div>
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <p class="text-sm text-base-content/60">Total Orders</p>
                            <h2 class="text-3xl font-bold text-secondary">0</h2>
                        </div>
                    </div>
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <p class="text-sm text-base-content/60">Pending Requests</p>
                            <h2 class="text-3xl font-bold text-warning">{{ $pendingCount ?? 0 }}</h2>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Recharge History Chart -->
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Recharge History</h3>
                            <canvas id="rechargeChart"></canvas>
                        </div>
                    </div>

                    <!-- Balance Add Chart -->
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Balance Add History</h3>
                            <canvas id="balanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Pie Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Operator Sales Chart -->
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Today's Operator Sales</h3>
                            <div class="flex justify-center">
                                <div style="max-width: 300px; max-height: 300px;">
                                    <canvas id="operatorChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Banking Chart -->
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Today's Mobile Banking</h3>
                            <div class="flex justify-center">
                                <div style="max-width: 300px; max-height: 300px;">
                                    <canvas id="bankingChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="drawer-side z-40">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 min-h-screen border-r border-base-200 transition-all duration-300">
                <div class="p-4 border-b border-base-200">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        @if(optional($settings)->company_logo_url)
                        <img src="{{ asset(optional($settings)->company_logo_url) }}" alt="Logo" class="h-10 w-10 object-contain rounded-lg">
                        @else
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        @endif
                        <span class="text-lg font-bold sidebar-text">{{ optional($settings)->company_name ?? 'Codecartel' }}</span>
                    </a>
                </div>
                <ul class="menu p-4 gap-1">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="active bg-primary text-primary-content">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="sidebar-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.backup') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Backup
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.pending.drive.requests') }}" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7l4-4m0 0l4 4m-4-4v18" />
                                </svg>
                                <span>Pending Request</span>
                            </div>
                            @if(isset($pendingCount) && $pendingCount > 0)
                            <span class="badge badge-error badge-sm">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                    @if($canManageResellers)
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Recharge History
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.all.history') }}">All History</a></li>
                                <li><a href="{{ route('admin.all.history', ['service' => 'flexi']) }}">Flexiload</a></li>
                                <li><a href="{{ route('admin.drive.history') }}">Drive</a></li>
                                <li><a href="{{ route('admin.internet.history') }}">Internet Pack</a></li>
                                <li><a href="{{ route('admin.all.history', ['service' => 'bkash']) }}">Bkash</a></li>
                                <li><a href="{{ route('admin.all.history', ['service' => 'nagad']) }}">Nagad</a></li>
                                <li><a href="{{ route('admin.all.history', ['service' => 'rocket']) }}">Rocket</a></li>
                                <li><a href="{{ route('admin.all.history', ['service' => 'upay']) }}">Upay</a></li>
                                <li><a>Success Refund</a></li>
                                <li><a>Islami Bank</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                Message Inbox
                            </summary>
                            <ul>
                                <li><a>Flexiload</a></li>
                                <li><a>Drive</a></li>
                                <li><a>Internet Pack</a></li>
                                <li><a>Bkash</a></li>
                                <li><a>Nagad</a></li>
                                <li><a>Rocket</a></li>
                                <li><a>Success Refund</a></li>
                                <li><a>Islami Bank</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details open>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                Offer Settings
                            </summary>
                            <ul>
                                <li>
                                    <a href="{{ route('admin.operator.create') }}" class="flex items-center gap-2">
                                        <span>Add Operator</span>
                                    </a>
                                </li>
                                <li><a href="{{ route('admin.regular.offer') }}">Regular Package</a></li>
                                <li><a href="{{ route('admin.drive.offer') }}">Drive Package</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Card Management
                            </summary>
                            <ul>
                                <li><a>Card Add</a></li>
                                <li><a>Card History</a></li>
                                <li><a>Card Management</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Bill Pay
                            </summary>
                            <ul>
                                <li><a>Pending Request</a></li>
                                <li><a>Bill Pay History</a></li>
                                <li><a>Bill Pay Settings</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Banking
                            </summary>
                            <ul>
                                <li><a>Pending Request</a></li>
                                <li><a>Bank History</a></li>
                                <li><a>Bank Settings</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4" />
                            </svg>
                            Sub Admin
                        </a>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Reseller
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.resellers') }}">All Reseller</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'house']) }}">House</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'dgm']) }}">DGM</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'dealer']) }}">Dealer</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'seller']) }}">Seller</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'retailer']) }}">Retailer</a></li>
                            </ul>
                        </details>
                    </li>
                    @endif
                    <li>
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Payment History
                        </a>
                    </li>
                    <li>
                        <details {{ request()->routeIs('admin.balance.report') || request()->routeIs('admin.daily.reports') || request()->routeIs('admin.operator.reports') || request()->routeIs('admin.sales.report') ? 'open' : '' }}>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Reports
                            </summary>
                            <ul>
                                <li><a>Receive reports</a></li>
                                <li><a href="{{ route('admin.balance.report') }}" class="{{ request()->routeIs('admin.balance.report') ? 'active bg-primary text-primary-content' : '' }}">Balance Reports</a></li>
                                <li><a href="{{ route('admin.operator.reports') }}" class="{{ request()->routeIs('admin.operator.reports') ? 'active bg-primary text-primary-content' : '' }}">Operator Reports</a></li>
                                <li><a href="{{ route('admin.daily.reports') }}" class="{{ request()->routeIs('admin.daily.reports') ? 'active bg-primary text-primary-content' : '' }}">Daily Reports</a></li>
                                <li><a>Total usages</a></li>
                                <li><a>Transaction</a></li>
                                <li><a>Trnx ID</a></li>
                                <li><a href="{{ route('admin.sales.report') }}" class="{{ request()->routeIs('admin.sales.report') ? 'active bg-primary text-primary-content' : '' }}">Sales Report</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details {{ request()->routeIs('admin.service.modules') || request()->routeIs('admin.recharge.block.list*') || request()->routeIs('admin.security.modual*') ? 'open' : '' }}>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                </svg>
                                Administration
                            </summary>
                            <ul>
                                <li>
                                    <a href="{{ route('admin.service.modules') }}" class="{{ request()->routeIs('admin.service.modules') ? 'active' : '' }}">
                                        Service Modules
                                    </a>
                                </li>
                                <li><a>Rate Modules</a></li>
                                <li>
                                    <a href="{{ route('admin.deposit') }}" class="{{ request()->routeIs('admin.deposit') ? 'active' : '' }}">
                                        Deposit
                                    </a>
                                </li>
                                <li><a>Modem List</a></li>
                                <li><a>Modem Device</a></li>
                                <li>
                                    <a href="{{ route('admin.recharge.block.list') }}" class="{{ request()->routeIs('admin.recharge.block.list*') ? 'active' : '' }}">
                                        Recharge Block List
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('api.index') }}" class="{{ request()->is('api-settings') ? 'active' : '' }}">
                                        Api Settings
                                    </a>
                                </li>
                                <li><a href="{{ route('admin.payment.gateway') }}" class="{{ request()->routeIs('admin.payment.gateway') ? 'active' : '' }}">Payment Gateway</a></li>
                                <li>
                                    <a href="{{ route('admin.security.modual') }}" class="{{ request()->routeIs('admin.security.modual*') ? 'active' : '' }}">
                                        Security Modual
                                    </a>
                                </li>
                                @if($canManageResellers)
                                <li><a href="{{ route('admin.deleted.accounts') }}">Deleted Accounts</a></li>
                                @endif
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Tools
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.branding') }}" class="{{ Route::is('admin.branding') ? 'active bg-primary text-white' : '' }}"><i class="fas fa-copyright w-5"></i> Branding</a></li>
                                <li>
                                    <a href="{{ route('admin.device.logs') }}"
                                        class="{{ request()->routeIs('admin.device.logs') ? 'active bg-primary text-primary-content font-semibold' : '' }}">
                                        Device Logs
                                    </a>
                                </li>
                                <li><a>Reseller Notice</a></li>
                                <li>
                                    <a href="{{ route('admin.notice.index') }}">
                                        Login Notice
                                    </a>
                                </li>
                                <li><a>Slides</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2h1a2 2 0 002-2v-1a2 2 0 012-2h1.945M7.757 15.757a3 3 0 104.486 0M12 10.5a3 3 0 110-6 3 3 0 010 6z" />
                                </svg>
                                Global
                            </summary>
                            <ul>
                                <li><a>Country</a></li>
                                <li><a>Operator</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Admin Account
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.profile') }}">My Profile</a></li>
                                <li><a href="{{ route('admin.manage.admins') }}">Manage Admin Users</a></li>
                                <li><a href="{{ route('admin.change.credentials') }}">Change Password & PIN</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <a href="{{ route('admin.complaints') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Complain
                        </a>
                    </li>
                    <li>
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Support
                        </a>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="sidebar-text">Settings</span>
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li>
                                <li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li>
                                <li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li>
                                <li><a href="{{ route('admin.firebase.config') }}">Firebase Credentials</a></li>
                                <li><a href="{{ route('admin.google.otp.config') }}">Google OTP</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </li>

                </ul>
                <div class="p-4 mt-auto border-t border-base-200">
                    <a href="/" class="btn btn-outline btn-sm w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Back to Website
                    </a>
                </div>
            </aside>
        </div>
    </div>

    @if($canManageResellers)
    <!-- Add User Modal -->
    <dialog id="add_user_modal" class="modal">
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-lg mb-4">Create New User</h3>
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="form-control mb-3">
                    <label class="label"><span class="label-text">Name</span></label>
                    <input type="text" name="name" class="input input-bordered w-full" required />
                </div>
                <div class="form-control mb-3">
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" class="input input-bordered w-full" required />
                </div>
                <div class="form-control mb-3">
                    <label class="label"><span class="label-text">Password</span></label>
                    <input type="password" name="password" class="input input-bordered w-full" required />
                </div>
                <div class="form-control mb-3">
                    <label class="label"><span class="label-text">PIN (4 digits)</span></label>
                    <input type="text" name="pin" maxlength="4" pattern="[0-9]{4}" class="input input-bordered w-full" required />
                </div>
                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Level</span></label>
                    <select name="level" class="select select-bordered w-full" required>
                        <option value="house">House</option>
                        <option value="dgm">DGM</option>
                        <option value="dealer">Dealer</option>
                        <option value="seller">Seller</option>
                        <option value="retailer">Retailer</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="label"><span class="label-text font-semibold">Permissions</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($resellerPermissionOptions as $permissionKey => $permissionLabel)
                        <label class="label cursor-pointer justify-start gap-2 bg-base-200 rounded-lg px-3 py-2">
                            <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" class="checkbox checkbox-primary checkbox-sm" />
                            <span class="label-text text-sm">{{ $permissionLabel }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="add_user_modal.close()" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    @endif
    <script>
        const drawer = document.getElementById('my-drawer');
        const sidebar = document.getElementById('sidebar');
        let isCollapsed = false;

        drawer.addEventListener('change', function() {
            if (window.innerWidth >= 1024) {
                if (isCollapsed) {
                    sidebar.classList.remove('w-16');
                    sidebar.classList.add('w-64');
                    document.querySelectorAll('.sidebar-text').forEach(el => el.classList.remove('hidden'));
                    isCollapsed = false;
                } else {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-16');
                    document.querySelectorAll('.sidebar-text').forEach(el => el.classList.add('hidden'));
                    isCollapsed = true;
                }
                drawer.checked = true;
            }
        });

        const pendingBulkSelectAll = document.getElementById('pending-bulk-select-all');
        if (pendingBulkSelectAll) {
            pendingBulkSelectAll.addEventListener('change', function() {
                document.querySelectorAll('.pending-bulk-checkbox').forEach(function(checkbox) {
                    checkbox.checked = pendingBulkSelectAll.checked;
                });
            });
        }

        const bulkSelectAll = document.getElementById('bulk-select-all');
        if (bulkSelectAll) {
            bulkSelectAll.addEventListener('change', function() {
                document.querySelectorAll('.bulk-user-checkbox').forEach(function(checkbox) {
                    checkbox.checked = bulkSelectAll.checked;
                });
            });
        }
    </script>
    @if(!isset($users) && !isset($deletedUsers))
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @php
    $chartPayload = [
    'rechargeAmounts' => [(float) ($yesterday ?? 0), (float) ($today ?? 0)],
    'balanceAmounts' => [(float) ($balanceYesterday ?? 0), (float) ($balanceToday ?? 0)],
    'operatorLabels' => data_get($operatorSales, '*.type'),
    'operatorData' => data_get($operatorSales, '*.total'),
    'bankingLabels' => data_get($bankingSales, '*.type'),
    'bankingData' => data_get($bankingSales, '*.total'),
    ];
    @endphp
    <script id="chart-payload-data" type="application/json">
        {
            !!json_encode($chartPayload) !!
        }
    </script>
    <script>
        (() => {
            if (typeof window.Chart === 'undefined') {
                console.warn('Chart.js failed to load for admin dashboard charts.');
                return;
            }

            const defaultPayload = {
                rechargeAmounts: [0, 0],
                balanceAmounts: [0, 0],
                operatorLabels: [],
                operatorData: [],
                bankingLabels: [],
                bankingData: [],
            };

            const chartPayloadElement = document.getElementById('chart-payload-data');

            let chartPayload = {
                ...defaultPayload
            };

            if (chartPayloadElement) {
                try {
                    chartPayload = {
                        ...defaultPayload,
                        ...JSON.parse(chartPayloadElement.textContent || '{}')
                    };
                } catch (error) {
                    console.warn('Invalid chart payload for admin dashboard charts.', error);
                }
            }

            const asNumberArray = (value, fallback = []) => Array.isArray(value) ?
                value.map((item) => Number(item) || 0) :
                fallback;

            const asStringArray = (value) => Array.isArray(value) ?
                value.map((item) => String(item)) : [];

            const rechargeAmounts = asNumberArray(chartPayload.rechargeAmounts, defaultPayload.rechargeAmounts);
            const balanceAmounts = asNumberArray(chartPayload.balanceAmounts, defaultPayload.balanceAmounts);
            const operatorLabels = asStringArray(chartPayload.operatorLabels);
            const operatorData = asNumberArray(chartPayload.operatorData);
            const bankingLabels = asStringArray(chartPayload.bankingLabels);
            const bankingData = asNumberArray(chartPayload.bankingData);

            const renderChart = (elementId, config) => {
                const element = document.getElementById(elementId);

                if (!element) {
                    return;
                }

                new window.Chart(element, config);
            };

            const buildPieData = (labels, data, colors) => ({
                labels: labels.length > 0 ? labels : ['No Data'],
                datasets: [{
                    data: data.length > 0 ? data : [1],
                    backgroundColor: data.length > 0 ? colors.slice(0, Math.max(data.length, 1)) : ['rgba(200, 200, 200, 0.7)'],
                    borderWidth: 2
                }]
            });

            renderChart('rechargeChart', {
                type: 'bar',
                data: {
                    labels: ['Yesterday', 'Today'],
                    datasets: [{
                        label: 'Recharge Amount (৳)',
                        data: rechargeAmounts,
                        backgroundColor: ['rgba(59, 130, 246, 0.5)', 'rgba(16, 185, 129, 0.5)'],
                        borderColor: ['rgb(59, 130, 246)', 'rgb(16, 185, 129)'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            renderChart('balanceChart', {
                type: 'bar',
                data: {
                    labels: ['Yesterday', 'Today'],
                    datasets: [{
                        label: 'Balance Added (৳)',
                        data: balanceAmounts,
                        backgroundColor: ['rgba(245, 158, 11, 0.5)', 'rgba(139, 92, 246, 0.5)'],
                        borderColor: ['rgb(245, 158, 11)', 'rgb(139, 92, 246)'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            renderChart('operatorChart', {
                type: 'pie',
                data: buildPieData(operatorLabels, operatorData, [
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(200, 200, 200, 0.7)'
                ]),
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += '৳' + context.parsed.toFixed(2);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            renderChart('bankingChart', {
                type: 'pie',
                data: buildPieData(bankingLabels, bankingData, [
                    'rgba(220, 38, 38, 0.7)',
                    'rgba(251, 146, 60, 0.7)',
                    'rgba(34, 197, 94, 0.7)',
                    'rgba(168, 85, 247, 0.7)',
                    'rgba(200, 200, 200, 0.7)'
                ]),
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += '৳' + context.parsed.toFixed(2);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        })();
    </script>
    @endif
</body>

</html>