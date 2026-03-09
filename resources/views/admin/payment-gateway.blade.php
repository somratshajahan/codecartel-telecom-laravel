<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Payment Gateway</a></div>
            </div>
            <div class="p-6">
                <div class="max-w-5xl mx-auto">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body space-y-6">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-3xl font-bold">💳 Payment Gateway Settings</h2>
                                    <p class="text-sm opacity-70">Administration এর Payment Gateway button থেকে এখন এই dedicated settings page open হবে।</p>
                                </div>
                                @if(session('success'))
                                <div class="badge badge-success badge-lg">{{ session('success') }}</div>
                                @endif

                                @if(session('warning'))
                                <div class="alert alert-warning text-sm">
                                    <span>{{ session('warning') }}</span>
                                </div>
                                @endif
                            </div>

                            @if($errors->any())
                            <div class="alert alert-error">
                                <ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                            @endif

                            <div class="alert alert-info text-sm">
                                Manual payment numbers আর online gateway credentials দুটোই এখান থেকে manage করতে পারবেন।
                            </div>

                            <form method="POST" action="{{ route('admin.payment.gateway.update') }}" class="space-y-6">
                                @csrf
                                <div class="space-y-3">
                                    <div>
                                        <h3 class="text-xl font-semibold">Manual Payment Numbers</h3>
                                        <p class="text-sm opacity-70">Bkash, Rocket, Nagad, Upay number এখান থেকে update করুন।</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="card bg-base-200 shadow-sm">
                                            <div class="card-body">
                                                <h3 class="card-title text-lg">Bkash</h3>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text font-medium">Bkash Number / Setting</span></label>
                                                    <input type="text" name="bkash" class="input input-bordered w-full" value="{{ old('bkash', $branding->bkash ?? '') }}" placeholder="e.g. 01XXXXXXXXX" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card bg-base-200 shadow-sm">
                                            <div class="card-body">
                                                <h3 class="card-title text-lg">Rocket</h3>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text font-medium">Rocket Number / Setting</span></label>
                                                    <input type="text" name="rocket" class="input input-bordered w-full" value="{{ old('rocket', $branding->rocket ?? '') }}" placeholder="e.g. 01XXXXXXXXX" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card bg-base-200 shadow-sm">
                                            <div class="card-body">
                                                <h3 class="card-title text-lg">Nagad</h3>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text font-medium">Nagad Number / Setting</span></label>
                                                    <input type="text" name="nagad" class="input input-bordered w-full" value="{{ old('nagad', $branding->nagad ?? '') }}" placeholder="e.g. 01XXXXXXXXX" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card bg-base-200 shadow-sm">
                                            <div class="card-body">
                                                <h3 class="card-title text-lg">Upay</h3>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text font-medium">Upay Number / Setting</span></label>
                                                    <input type="text" name="upay" class="input input-bordered w-full" value="{{ old('upay', $branding->upay ?? '') }}" placeholder="e.g. 01XXXXXXXXX" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <h3 class="text-xl font-semibold">Online Gateway Credentials</h3>
                                            <p class="text-sm opacity-70">SSLCommerz আর AmarPay credential এখান থেকে save করতে পারবেন।</p>
                                        </div>

                                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                                            <div class="card bg-base-200 shadow-sm">
                                                <div class="card-body space-y-4">
                                                    <h3 class="card-title text-lg">SSLCommerz</h3>

                                                    <div class="form-control">
                                                        <label class="label"><span class="label-text font-medium">Store ID</span></label>
                                                        <input type="text" name="sslcommerz_store_id" class="input input-bordered w-full" value="{{ old('sslcommerz_store_id', $branding->sslcommerz_store_id ?? '') }}" placeholder="e.g. testbox" />
                                                    </div>

                                                    <div class="form-control">
                                                        <label class="label"><span class="label-text font-medium">Store Password</span></label>
                                                        <input type="password" name="sslcommerz_store_password" class="input input-bordered w-full" value="{{ old('sslcommerz_store_password', $branding->sslcommerz_store_password ?? '') }}" placeholder="e.g. test_password" />
                                                    </div>

                                                    <div class="form-control">
                                                        <label class="label"><span class="label-text font-medium">Mode</span></label>
                                                        <select name="sslcommerz_mode" class="select select-bordered w-full">
                                                            <option value="sandbox" @selected(old('sslcommerz_mode', $branding->sslcommerz_mode ?? 'sandbox') === 'sandbox')>Sandbox</option>
                                                            <option value="live" @selected(old('sslcommerz_mode', $branding->sslcommerz_mode ?? 'sandbox') === 'live')>Live</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card bg-base-200 shadow-sm">
                                                <div class="card-body space-y-4">
                                                    <h3 class="card-title text-lg">AmarPay</h3>

                                                    <div class="form-control">
                                                        <label class="label"><span class="label-text font-medium">Store ID</span></label>
                                                        <input type="text" name="amarpay_store_id" class="input input-bordered w-full" value="{{ old('amarpay_store_id', $branding->amarpay_store_id ?? '') }}" placeholder="e.g. amarpay_store" />
                                                    </div>

                                                    <div class="form-control">
                                                        <label class="label"><span class="label-text font-medium">Signature Key</span></label>
                                                        <input type="password" name="amarpay_signature_key" class="input input-bordered w-full" value="{{ old('amarpay_signature_key', $branding->amarpay_signature_key ?? '') }}" placeholder="e.g. signature_key" />
                                                    </div>

                                                    <div class="form-control">
                                                        <label class="label"><span class="label-text font-medium">Mode</span></label>
                                                        <select name="amarpay_mode" class="select select-bordered w-full">
                                                            <option value="sandbox" @selected(old('amarpay_mode', $branding->amarpay_mode ?? 'sandbox') === 'sandbox')>Sandbox</option>
                                                            <option value="live" @selected(old('amarpay_mode', $branding->amarpay_mode ?? 'sandbox') === 'live')>Live</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-center md:justify-end">
                                    <button type="submit" class="btn btn-primary btn-wide">Save Payment Gateway Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
                                <li><a href="{{ route('api.index') }}">Api Settings</a></li>
                                <li><a href="{{ route('admin.payment.gateway') }}" class="active">Payment Gateway</a></li>
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