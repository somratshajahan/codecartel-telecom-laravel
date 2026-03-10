<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ filled(data_get($selectedManualMethod, 'name')) ? ((strtolower((string) data_get($selectedManualMethod, 'key')) === 'bkash' ? 'bKash' : data_get($selectedManualMethod, 'name')) . ' Request') : 'Add Balance' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
    $canAddBalance = $user->hasPermission('add_balance');
    $canFlexi = true;
    $canDrive = $user->hasPermission('drive');
    $canInternet = $user->hasPermission('internet');
    $canPendingRequests = $user->hasPermission('pending_requests');
    $canAllHistory = $user->hasPermission('all_history');
    $canDriveHistory = $user->hasPermission('drive_history');
    $canProfile = $user->hasPermission('profile');
    $canComplaints = $user->hasPermission('complaints');
    $sslCommerzEnabled = filled(optional($branding)->sslcommerz_store_id) && filled(optional($branding)->sslcommerz_store_password);
    $currentRouteName = request()->route()?->getName();
    $isAddBalanceRoute = in_array($currentRouteName, ['user.add.balance', 'user.bkash', 'user.nagad', 'user.rocket', 'user.upay'], true);
    $selectedManualMethodName = data_get($selectedManualMethod, 'name');
    $selectedManualMethodNumber = data_get($selectedManualMethod, 'number');
    $selectedManualMethodRoute = data_get($selectedManualMethod, 'route_name');
    $selectedManualMethodKey = strtolower((string) data_get($selectedManualMethod, 'key', ''));
    $selectedManualMethodDisplayName = $selectedManualMethodKey === 'bkash' ? 'bKash' : $selectedManualMethodName;
    $isDedicatedManualPage = filled($selectedManualMethodName);
    $selectedManualMethodAvailable = filled($selectedManualMethodNumber);
    $pageTitle = $isDedicatedManualPage ? ($selectedManualMethodDisplayName . ' Request') : 'Add Balance';
    $redirectRouteName = $selectedManualMethodRoute ?: ($currentRouteName ?: 'user.add.balance');
    $showManualSubmitForm = $isDedicatedManualPage || ($manualMethods ?? collect())->isNotEmpty();
    $manualRequestTypeOptions = ['Cash IN', 'cash out', 'send money'];
    @endphp

    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none lg:hidden">
                    <label for="my-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a href="{{ route('dashboard') }}" class="px-2 text-xl font-bold">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</a>
                </div>
                <div class="flex-none flex items-center gap-2">
                    @include('partials.theme-toggle')
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="font-bold" id="navBalance">৳ {{ number_format($user->main_bal ?? 0, 2) }}</span>
                        </div>
                        <ul tabindex="0" class="mt-3 z-1 p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            <li class="menu-title">Balance Details</li>
                            <li><a>Main: ৳ {{ number_format($user->main_bal ?? 0, 2) }}</a></li>
                            <li><a>Drive: ৳ {{ number_format($user->drive_bal ?? 0, 2) }}</a></li>
                            <li><a>Bank: ৳ {{ number_format($user->bank_bal ?? 0, 2) }}</a></li>
                        </ul>
                    </div>
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center overflow-hidden">
                                @if($user->profile_picture)
                                <img src="{{ asset($user->profile_picture) }}" alt="Profile" class="w-full h-full object-cover">
                                @else
                                <span class="font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                @endif
                            </div>
                        </div>
                        <ul tabindex="0" class="mt-3 z-1 p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            @if($canProfile)
                            <li><a href="{{ route('user.profile') }}">Profile</a></li>
                            <li><a href="{{ route('user.profile.google-otp') }}">Google OTP</a></li>
                            <li><a href="{{ route('user.profile.api') }}">API</a></li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <span class="font-semibold hidden sm:inline">{{ $user->name }}</span>
                </div>
            </div>

            <main class="flex-1 p-4 md:p-6">
                <div class="mx-auto w-full max-w-6xl">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-bold">{{ $pageTitle }}</h1>
                            <p class="text-base-content/60 mt-1">
                                @if($isDedicatedManualPage)
                                {{ $selectedManualMethodDisplayName }} request submit করার পর user/admin pending-এ show হবে, আর approve হলে all history এবং {{ $selectedManualMethodDisplayName }} history-তে show হবে।
                                @else
                                Online payment বা manual request submit করে দ্রুত balance add করুন।
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('dashboard') }}" class="btn btn-ghost">← Back to Dashboard</a>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success mb-6">
                        <span>{{ session('success') }}</span>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-warning mb-6">
                        <span>{{ session('error') }}</span>
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert alert-error mb-6">
                        <div>
                            <div class="font-semibold">Please fix the following issues:</div>
                            <ul class="list-disc pl-5 text-sm mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    <div class="grid gap-4 md:grid-cols-3 mb-8">
                        <div class="stat bg-base-100 rounded-2xl shadow">
                            <div class="stat-title">Main Balance</div>
                            <div class="stat-value text-primary">৳{{ number_format($user->main_bal ?? 0, 2) }}</div>
                        </div>
                        <div class="stat bg-base-100 rounded-2xl shadow">
                            <div class="stat-title">Drive Balance</div>
                            <div class="stat-value text-secondary">৳{{ number_format($user->drive_bal ?? 0, 2) }}</div>
                        </div>
                        <div class="stat bg-base-100 rounded-2xl shadow">
                            <div class="stat-title">Bank Balance</div>
                            <div class="stat-value text-accent">৳{{ number_format($user->bank_bal ?? 0, 2) }}</div>
                        </div>
                    </div>

                    @if($sslCommerzEnabled && ! $isDedicatedManualPage)
                    <div class="card bg-base-100 shadow-xl mb-8 border border-primary/20">
                        <div class="card-body">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="card-title">Pay with SSLCommerz</h2>
                                    <p class="text-sm text-base-content/70">Online payment success হলে balance automatically main balance-এ add হবে।</p>
                                </div>
                                <span class="badge badge-primary badge-lg">Instant Online Payment</span>
                            </div>
                            <form method="POST" action="{{ route('user.add.balance.sslcommerz.start') }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4 items-end mt-3">
                                @csrf
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Amount</span></label>
                                    <input type="number" step="0.01" min="1" name="sslcommerz_amount" value="{{ old('sslcommerz_amount') }}" class="input input-bordered" placeholder="Enter amount" required />
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="btn btn-primary w-full md:w-auto">Pay with SSLCommerz</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if($isDedicatedManualPage)
                    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.25fr)] mb-8">
                        <div class="card bg-base-100 shadow-xl border border-base-300">
                            <div class="card-body gap-5">
                                <div>
                                    <div class="badge {{ data_get($selectedManualMethod, 'color', 'bg-primary') }} text-white">{{ $pageTitle }}</div>
                                    <h2 class="text-2xl font-bold mt-3">Send {{ $selectedManualMethodDisplayName }}</h2>
                                    @if($selectedManualMethodAvailable)
                                    <p class="text-sm text-base-content/60 mt-2">Payment Number: {{ $selectedManualMethodNumber }}</p>
                                    @endif
                                </div>

                                @if($showManualSubmitForm)
                                <form method="POST" action="{{ route('user.add.balance.submit') }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="redirect_route" value="{{ $redirectRouteName }}" />
                                    <input type="hidden" name="method" value="{{ $selectedManualMethodName }}" />

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">Number</span></label>
                                        <input type="text" name="sender_number" value="{{ old('sender_number') }}" class="input input-bordered" placeholder="eg: 0171XXXXXXX" required />
                                        <label class="label"><span class="label-text-alt">[ Min Number 11, Max Number 11 ]</span></label>
                                    </div>

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">Amount</span></label>
                                        <input type="number" step="0.01" min="500" max="25000" name="amount" value="{{ old('amount') }}" class="input input-bordered" placeholder="eg: 100" required />
                                        <label class="label"><span class="label-text-alt">[ Min Amount 500, Max Amount 25000 ]</span></label>
                                    </div>

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">Type</span></label>
                                        <select name="type" class="select select-bordered" required>
                                            <option value="">Select Type</option>
                                            @foreach($manualRequestTypeOptions as $typeOption)
                                            <option value="{{ $typeOption }}" {{ old('type') === $typeOption ? 'selected' : '' }}>{{ $typeOption }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">User PIN</span></label>
                                        <input type="password" inputmode="numeric" maxlength="4" name="pin" class="input input-bordered" placeholder="Enter 4 digit PIN" required />
                                    </div>

                                    <button type="submit" class="btn btn-primary w-full">send</button>
                                </form>
                                @endif
                            </div>
                        </div>

                        <div class="card bg-base-100 shadow-xl border border-base-300">
                            <div class="card-body">
                                <div class="flex items-center justify-between gap-3">
                                    <h2 class="card-title">Last 10 Requests</h2>
                                    <span class="badge badge-outline">{{ $selectedManualMethodDisplayName }}</span>
                                </div>

                                @if(($recentRequests ?? collect())->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra">
                                        <thead>
                                            <tr>
                                                <th>Number</th>
                                                <th>Amount</th>
                                                <th>Cost</th>
                                                <th>Trnx</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($recentRequests ?? collect()) as $request)
                                            @php
                                            $status = strtolower((string) $request->status);
                                            $badgeClass = $status === 'approved'
                                            ? 'badge-success'
                                            : (in_array($status, ['failed', 'rejected', 'cancelled'], true) ? 'badge-error' : 'badge-warning');
                                            @endphp
                                            <tr>
                                                <td>{{ $request->sender_number }}</td>
                                                <td>৳{{ number_format($request->amount, 2) }}</td>
                                                <td>৳{{ number_format((float) ($request->cost ?? $request->amount), 2) }}</td>
                                                <td>{{ $request->transaction_id }}</td>
                                                <td><span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <p class="text-sm text-base-content/60">No Requests Found.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @else
                    @if($manualMethods->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
                        @foreach($manualMethods as $method)
                        <a href="{{ filled($method['route_name'] ?? null) ? route($method['route_name']) : route('user.add.balance') }}" class="card bg-base-100 shadow-xl border border-base-300 hover:border-primary transition-colors">
                            <div class="card-body text-center">
                                <div class="badge {{ $method['color'] }} text-white mx-auto">{{ $method['name'] }}</div>
                                <h2 class="text-2xl font-bold mt-3">{{ $method['number'] }}</h2>
                                <p class="text-sm text-base-content/60">{{ filled($method['route_name'] ?? null) ? 'Dedicated request page open করতে click করুন' : 'এই নাম্বারে cash in/send money করুন' }}</p>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @elseif(!$sslCommerzEnabled)
                    <div class="alert alert-warning mb-8">
                        <span>এখনও কোনো payment method configure করা হয়নি। অনুগ্রহ করে admin-এর সাথে যোগাযোগ করুন।</span>
                    </div>
                    @endif

                    @if($showManualSubmitForm)
                    <div class="card bg-base-100 shadow-xl mb-8">
                        <div class="card-body">
                            <h2 class="card-title">Submit Payment Request</h2>
                            <form method="POST" action="{{ route('user.add.balance.submit') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @csrf
                                <input type="hidden" name="redirect_route" value="{{ $redirectRouteName }}" />
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Method</span></label>
                                    <select name="method" class="select select-bordered" required>
                                        <option value="">Select method</option>
                                        @foreach($manualMethods as $method)
                                        <option value="{{ $method['name'] }}" {{ old('method') === $method['name'] ? 'selected' : '' }}>{{ $method['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Number</span></label>
                                    <input type="text" name="sender_number" value="{{ old('sender_number') }}" class="input input-bordered" placeholder="eg: 0171XXXXXXX" required />
                                    <label class="label"><span class="label-text-alt">[ Min Number 11, Max Number 11 ]</span></label>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Amount</span></label>
                                    <input type="number" step="0.01" min="500" max="25000" name="amount" value="{{ old('amount') }}" class="input input-bordered" placeholder="eg: 100" required />
                                    <label class="label"><span class="label-text-alt">[ Min Amount 500, Max Amount 25000 ]</span></label>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Type</span></label>
                                    <select name="type" class="select select-bordered" required>
                                        <option value="">Select Type</option>
                                        @foreach($manualRequestTypeOptions as $typeOption)
                                        <option value="{{ $typeOption }}" {{ old('type') === $typeOption ? 'selected' : '' }}>{{ $typeOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">User PIN</span></label>
                                    <input type="password" inputmode="numeric" maxlength="4" name="pin" class="input input-bordered" placeholder="Enter 4 digit PIN" required />
                                </div>
                                <div class="md:col-span-2 flex justify-end">
                                    <button type="submit" class="btn btn-primary">Submit Request</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">How to add balance</h2>
                                <ol class="list-decimal pl-5 space-y-2 text-sm text-base-content/80">
                                    <li>SSLCommerz দিয়ে online payment করলে payment verify হওয়ার পর balance automatically add হবে。</li>
                                    <li>Manual payment করলে উপরের যেকোনো payment number-এ টাকা পাঠান।</li>
                                    <li>Number, amount, এবং request type select করে form submit করুন।</li>
                                    <li>Manual request-এর ক্ষেত্রে admin approval-এর পর balance update হবে।</li>
                                </ol>
                            </div>
                        </div>

                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">Support</h2>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-semibold">Phone:</span> {{ optional($branding)->alert_no ?: 'Not configured' }}</p>
                                    <p><span class="font-semibold">WhatsApp:</span> {{ optional($branding)->whatsapp_link ?: 'Not configured' }}</p>
                                    <p class="text-base-content/60">Online বা manual payment-এ সমস্যা হলে support-এ যোগাযোগ করুন।</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 xl:grid-cols-2 mt-8">
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">Recent Online Payments</h2>
                                @if(($recentSslCommerzTransactions ?? collect())->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra">
                                        <thead>
                                            <tr>
                                                <th>Gateway</th>
                                                <th>Transaction ID</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($recentSslCommerzTransactions ?? collect()) as $transaction)
                                            @php
                                            $status = strtolower((string) $transaction->status);
                                            $badgeClass = in_array($status, ['approved'], true)
                                            ? 'badge-success'
                                            : (in_array($status, ['failed', 'cancelled'], true) ? 'badge-error' : 'badge-warning');
                                            @endphp
                                            <tr>
                                                <td><span class="badge badge-secondary">SSLCommerz</span></td>
                                                <td>{{ $transaction->tran_id }}</td>
                                                <td>৳{{ number_format($transaction->amount, 2) }}</td>
                                                <td><span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span></td>
                                                <td>{{ optional($transaction->credited_at ?? $transaction->created_at)->format('d M Y H:i') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <p class="text-sm text-base-content/60">No SSLCommerz payment found yet.</p>
                                @endif
                            </div>
                        </div>

                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">Recent Manual Requests</h2>
                                @if(($recentRequests ?? collect())->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra">
                                        <thead>
                                            <tr>
                                                <th>Method</th>
                                                <th>Number</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Trnx</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($recentRequests ?? collect()) as $request)
                                            @php
                                            $status = strtolower((string) $request->status);
                                            $badgeClass = $status === 'approved'
                                            ? 'badge-success'
                                            : (in_array($status, ['failed', 'rejected', 'cancelled'], true) ? 'badge-error' : 'badge-warning');
                                            @endphp
                                            <tr>
                                                <td><span class="badge badge-primary">{{ $request->method }}</span></td>
                                                <td>{{ $request->sender_number }}</td>
                                                <td>{{ $request->note ?: '-' }}</td>
                                                <td>৳{{ number_format($request->amount, 2) }}</td>
                                                <td>{{ $request->transaction_id }}</td>
                                                <td><span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span></td>
                                                <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <p class="text-sm text-base-content/60">No manual payment request found yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </main>

            <footer class="footer items-center p-4 bg-base-300 text-base-content">
                <div class="items-center grid-flow-col">
                    <p>Copyright © 2026 - All right reserved by Codecartel Telecom | Version 1.0.0</p>
                </div>
            </footer>
        </div>

        <div class="drawer-side">
            <label for="my-drawer" class="drawer-overlay"></label>
            <ul class="menu p-4 w-60 min-h-full bg-base-100 text-base-content">
                <li>
                    <a href="{{ route('dashboard') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                </li>
                @if($canAddBalance)
                <li>
                    <a class="{{ $isAddBalanceRoute ? 'active bg-primary text-primary-content' : '' }}" href="{{ route('user.add.balance') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Balance
                    </a>
                </li>
                @endif
                @if($canAddBalance || $canFlexi || $canInternet || $canDrive)
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                New Request
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="{{ route('user.flexi') }}">Flexiload</a></li>
                            @if($canInternet)
                            <li><a href="{{ route('user.internet') }}">Internet Pack</a></li>
                            @endif
                            @if($canDrive)
                            <li><a href="{{ route('user.drive') }}">Drive</a></li>
                            @endif
                            @if($canAddBalance)
                            <li><a href="{{ route('user.bkash') }}">Bkash</a></li>
                            <li><a href="{{ route('user.nagad') }}">Nagad</a></li>
                            <li><a href="{{ route('user.rocket') }}">Rocket</a></li>
                            <li><a href="{{ route('user.upay') }}">Upay</a></li>
                            @endif
                            <li><a href="#">Islami Bank</a></li>
                            <li><a href="{{ route('user.flexi') }}">Bulk Flexi</a></li>
                        </ul>
                    </details>
                </li>
                @endif
                @if($canPendingRequests)
                <li>
                    <a href="{{ route('user.pending.requests') }}" class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Pending Request</span>
                        </div>
                        @if(isset($pendingRequests) && $pendingRequests->count() > 0)
                        <span class="badge badge-error badge-sm">{{ $pendingRequests->count() }}</span>
                        @endif
                    </a>
                </li>
                @endif
                @if($canAllHistory || $canDriveHistory)
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5M21 21v-5h-5M4 4l16 16" />
                                </svg>
                                History
                            </span>
                        </summary>
                        <ul class="p-2">
                            @if($canAllHistory)
                            <li><a href="{{ route('user.all.history') }}">All history</a></li>
                            <li><a href="{{ route('user.all.history', ['type' => 'flexi']) }}">Flexiload</a></li>
                            <li><a href="{{ route('user.all.history', ['type' => 'bkash']) }}">Bkash</a></li>
                            <li><a href="{{ route('user.all.history', ['type' => 'nagad']) }}">Nagad</a></li>
                            <li><a href="{{ route('user.all.history', ['type' => 'rocket']) }}">Rocket</a></li>
                            <li><a href="{{ route('user.all.history', ['type' => 'upay']) }}">Upay</a></li>
                            @endif
                            <li><a href="#">Internet Pack</a></li>
                            @if($canDriveHistory)
                            <li><a href="{{ route('user.drive.history') }}">Drive</a></li>
                            @endif
                            <li><a href="#">Islami Bank</a></li>
                        </ul>
                    </details>
                </li>
                @endif
                @if($canProfile)
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                                </svg>
                                Prepaid Card
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="#">Buy Card</a></li>
                            <li><a href="#">Card History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14h6m-6 4h6m2 2h-10a2 2 0 01-2-2V7a2 2 0 012-2h10a2 2 0 012 2v11a2 2 0 01-2 2z" />
                                </svg>
                                Bill Pay
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="#">New Bill Pay</a></li>
                            <li><a href="#">History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h16V4M4 20v-5h16v5M4 12h16" />
                                </svg>
                                Internet Bank
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="#">Banking Request</a></li>
                            <li><a href="#">History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8v-4a4 4 0 00-3-3.87" />
                                </svg>
                                SMS
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="#">Send SMS</a></li>
                            <li><a href="#">History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87" />
                        </svg>
                        Reseller
                    </a>
                </li>
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.008-3 2.25S10.343 12.5 12 12.5s3 1.008 3 2.25S13.657 17 12 17m0-9V7m0 10v-1" />
                                </svg>
                                Payment
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="#">Return Found</a></li>
                            <li><a href="#">Payment History</a></li>
                            <li><a href="#">Receive History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M9 13v6M13 9v10M17 5v14" />
                                </svg>
                                Reports
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="#">Balance Reports</a></li>
                            <li><a href="#">Cost Profit</a></li>
                            <li><a href="#">Total reports</a></li>
                            <li><a href="#">Trnx Reports</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                My Accounts
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="{{ route('user.profile') }}">My Profile</a></li>
                            <li><a href="{{ route('user.profile.google-otp') }}">Google OTP</a></li>
                            <li><a href="#">Email/Mobile OTP</a></li>
                            <li><a href="{{ route('user.profile.api') }}">API</a></li>
                            <li><a href="#">My Rates</a></li>
                            <li><a href="#">Access Log</a></li>
                            <li><a href="#">Reseller Device logs</a></li>
                            <li><a href="#">Change pin</a></li>
                            <li><a href="#">Change Password</a></li>
                        </ul>
                    </details>
                </li>
                @endif
                @if($canComplaints)
                <li>
                    <a href="{{ route('complaints.index') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Complain
                    </a>
                </li>
                @endif
                <li>
                    <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</body>

</html>