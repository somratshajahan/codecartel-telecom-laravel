<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Dashboard' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
    $apiDocs = $apiDocs ?? [];
    $usageStats = $usageStats ?? [
    'total_spent' => 0,
    'total_recharges' => 0,
    'period_label' => 'No recharge history yet',
    'recharge_desc' => 'Start with your first successful recharge',
    'last_recharge_label' => 'No recharge yet',
    'last_recharge_operator' => 'No successful recharge found',
    ];
    @endphp
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
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
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
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
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
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

            <!-- Main Content -->
            <main class="flex-1 p-6">
                @if(isset($showPendingPage) && $showPendingPage)
                <h1 class="text-3xl font-bold mb-6">My Pending Requests</h1>
                <div class="card bg-base-100 shadow-lg mb-8">
                    <div class="card-body">
                        @if($pendingRequests->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>SL</th>
                                        <th>Type</th>
                                        <th>Operator</th>
                                        <th>Package</th>
                                        <th>Mobile</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingRequests as $req)
                                    @php
                                    $isManualPayment = ($req->request_category ?? '') === 'manual_payment';
                                    $isFlexiRequest = ($req->request_type ?? '') === 'Flexi';
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @if($isFlexiRequest)
                                            <a href="{{ route('user.all.history', ['type' => 'flexi']) }}" class="badge badge-info hover:badge-secondary transition-colors">{{ $req->request_type }}</a>
                                            @else
                                            <span class="badge badge-info">{{ $req->request_type ?? 'Drive' }}</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-primary">{{ $req->operator }}</span></td>
                                        <td>{{ ($isFlexiRequest || $isManualPayment) ? ($req->type ?? 'N/A') : ($req->package->name ?? 'N/A') }}</td>
                                        <td>{{ $req->mobile }}</td>
                                        <td>৳{{ number_format($req->amount, 2) }}</td>
                                        <td><span class="badge badge-warning">Pending</span></td>
                                        <td>{{ $req->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-base-content/60 text-center">No pending requests</p>
                        @endif
                    </div>
                </div>
                @else
                <h1 class="text-3xl font-bold">Welcome, {{ $user->name }}!</h1>
                <p class="text-base-content/60 mb-8">Here's a quick overview of your account.</p>

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

                <!-- Row 1: Add Balance & Services -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
                    <!-- Add Balance Button -->
                    @if($canAddBalance)
                    <a href="{{ route('user.add.balance') }}" class="card bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <h2 class="card-title">Add Balance</h2>
                        </div>
                    </a>
                    @endif
                    <a href="{{ route('user.flexi') }}" class="card bg-primary text-primary-content shadow-lg hover:bg-primary-focus transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                            <h2 class="card-title">Flexiload</h2>
                        </div>
                    </a>
                    @if($canDrive)
                    <a href="{{ route('user.drive') }}" class="card bg-secondary text-secondary-content shadow-lg hover:bg-secondary-focus transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-5.26-1.72-4.5 4.5 0 0 0-8.22 5.472A4.522 4.522 0 0 0 2.25 15Z" />
                            </svg>
                            <h2 class="card-title">Drive</h2>
                        </div>
                    </a>
                    @endif
                    @if($canInternet)
                    <a href="{{ route('user.internet') }}" class="card bg-accent text-accent-content shadow-lg hover:bg-accent-focus transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12 18.375a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <h2 class="card-title">Internet</h2>
                        </div>
                    </a>
                    @endif
                    <a href="{{ route('user.bkash') }}" class="card bg-info text-info-content shadow-lg hover:bg-info-focus transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 3a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m15 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m15 0-3-3m0 0-3 3m3-3V6" />
                            </svg>
                            <h2 class="card-title">Bkash</h2>
                        </div>
                    </a>
                </div>

                <!-- Row 2: More Services -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
                    <a href="{{ route('user.nagad') }}" class="card bg-success text-success-content shadow-lg hover:bg-success-focus transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 3a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m15 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m15 0-3-3m0 0-3 3m3-3V6" />
                            </svg>
                            <h2 class="card-title">Nagad</h2>
                        </div>
                    </a>
                    <a href="{{ route('user.rocket') }}" class="card bg-warning text-warning-content shadow-lg hover:bg-warning-focus transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 3a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m15 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m15 0-3-3m0 0-3 3m3-3V6" />
                            </svg>
                            <h2 class="card-title">Rocket</h2>
                        </div>
                    </a>
                    <a href="{{ route('user.upay') }}" class="card bg-error text-error-content shadow-lg hover:bg-error-focus transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 3a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m15 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m15 0-3-3m0 0-3 3m3-3V6" />
                            </svg>
                            <h2 class="card-title">Upay</h2>
                        </div>
                    </a>
                    <a href="#" class="card bg-blue-500 text-white shadow-lg hover:bg-blue-600 transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18" />
                            </svg>
                            <h2 class="card-title">Islami Bank</h2>
                        </div>
                    </a>
                    <!-- Returns with changed color -->
                    <a href="#" class="card bg-rose-600 text-white shadow-lg hover:bg-rose-700 transition-colors">
                        <div class="card-body items-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                            <h2 class="card-title">Returns</h2>
                        </div>
                    </a>
                </div>

                <!-- Total Usage Section -->
                <div class="card bg-base-100 shadow-lg mb-8">
                    <div class="card-body">
                        <h2 class="card-title">My Pending Requests</h2>
                        @if(isset($pendingRequests) && $pendingRequests->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>SL</th>
                                        <th>Type</th>
                                        <th>Operator</th>
                                        <th>Package</th>
                                        <th>Mobile</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingRequests as $req)
                                    @php
                                    $isManualPayment = ($req->request_category ?? '') === 'manual_payment';
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><span class="badge badge-info">{{ $req->request_type ?? 'Drive' }}</span></td>
                                        <td><span class="badge badge-primary">{{ $req->operator }}</span></td>
                                        <td>{{ (($req->request_type ?? '') === 'Flexi' || $isManualPayment) ? ($req->type ?? 'N/A') : ($req->package->name ?? 'N/A') }}</td>
                                        <td>{{ $req->mobile }}</td>
                                        <td>৳{{ number_format($req->amount, 2) }}</td>
                                        <td><span class="badge badge-warning">Pending</span></td>
                                        <td>{{ $req->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-base-content/60">No pending requests</p>
                        @endif
                    </div>
                </div>

                <!-- Total Usage Section -->
                <div class="card bg-base-100 shadow-lg mb-8">
                    <div class="card-body">
                        <h2 class="card-title">Total Usage</h2>
                        <div class="stats stats-vertical lg:stats-horizontal shadow w-full">
                            <div class="stat">
                                <div class="stat-figure text-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="stat-title">Total Spent</div>
                                <div class="stat-value text-primary">৳ {{ number_format($usageStats['total_spent'] ?? 0, 2) }}</div>
                                <div class="stat-desc">{{ $usageStats['period_label'] ?? 'No recharge history yet' }}</div>
                            </div>

                            <div class="stat">
                                <div class="stat-figure text-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="stat-title">Total Recharges</div>
                                <div class="stat-value text-secondary">{{ number_format($usageStats['total_recharges'] ?? 0) }}</div>
                                <div class="stat-desc text-success">{{ $usageStats['recharge_desc'] ?? 'Start with your first successful recharge' }}</div>
                            </div>

                            <div class="stat">
                                <div class="stat-figure text-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="stat-title">Last recharge</div>
                                <div class="stat-value">{{ $usageStats['last_recharge_label'] ?? 'No recharge yet' }}</div>
                                <div class="stat-desc">{{ $usageStats['last_recharge_operator'] ?? 'No successful recharge found' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Last Payment Section -->
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <h2 class="card-title">Last Payments</h2>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2026-03-01</td>
                                            <td>৳ 500</td>
                                            <td>bKash</td>
                                            <td><span class="badge badge-success">Success</span></td>
                                        </tr>
                                        <tr>
                                            <td>2026-02-25</td>
                                            <td>৳ 1000</td>
                                            <td>Nagad</td>
                                            <td><span class="badge badge-success">Success</span></td>
                                        </tr>
                                        <tr>
                                            <td>2026-02-24</td>
                                            <td>৳ 200</td>
                                            <td>Rocket</td>
                                            <td><span class="badge badge-warning">Pending</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Last Receive Section -->
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <h2 class="card-title">Last Received</h2>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lastReceived as $received)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($received->created_at)->format('Y-m-d') }}</td>
                                            <td>৳ {{ number_format($received->amount, 2) }}</td>
                                            <td>{{ $received->type ?? 'Balance Added' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="text-center">No balance received yet</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!empty($apiDocs))
                <div class="card bg-base-100 shadow-lg mb-8">
                    <div class="card-body space-y-6">
                        <div>
                            <h2 class="card-title text-2xl">Simple Provider API Documentation</h2>
                            <p class="text-sm text-base-content/70">Client panel integration-er jonno quick copy-paste sample format.</p>
                        </div>

                        @include('partials.provider-api-docs-content', ['apiDocs' => $apiDocs])
                    </div>
                </div>
                @endif
                @endif
            </main>

            <!-- Footer -->
            <footer class="footer items-center p-4 bg-base-300 text-base-content">
                <div class="items-center grid-flow-col">
                    <p>Copyright © 2026 - All right reserved by Codecartel Telecom | Version 1.0.0</p>
                </div>
            </footer>
        </div>

        <!-- Sidebar -->
        <div class="drawer-side">
            <label for="my-drawer" class="drawer-overlay"></label>
            <ul class="menu p-4 w-60 min-h-full bg-base-100 text-base-content">
                <li>
                    <a class="{{ isset($showPendingPage) && $showPendingPage ? '' : 'active bg-primary text-primary-content' }}" href="{{ route('dashboard') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                </li>
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
                    <a href="{{ route('user.pending.requests') }}" class="flex items-center justify-between {{ isset($showPendingPage) && $showPendingPage ? 'active bg-primary text-primary-content' : '' }}">
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
                            <li><a href="{{ route('user.all.history', ['type' => 'bkash']) }}">Bkash</a></li>
                            <li><a href="{{ route('user.all.history', ['type' => 'nagad']) }}">Nagad</a></li>
                            <li><a href="{{ route('user.all.history', ['type' => 'rocket']) }}">Rocket</a></li>
                            <li><a href="{{ route('user.all.history', ['type' => 'upay']) }}">Upay</a></li>
                            @endif
                            <li><a href="{{ route('user.all.history', ['type' => 'flexi']) }}">Flexiload</a></li>
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