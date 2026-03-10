<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
    $pageTitle = match ($historyType ?? 'all') {
    'flexi' => 'Flexiload History',
    'internet' => 'Internet Pack History',
    'bkash' => 'Bkash History',
    'nagad' => 'Nagad History',
    'rocket' => 'Rocket History',
    'upay' => 'Upay History',
    default => 'My History',
    };
    @endphp
    <title>{{ $pageTitle }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
    $historyMeta = [
    'all' => [
    'title' => 'My History',
    'subtitle' => 'All approved, failed, and cancelled history is shown here.',
    'badge_class' => 'badge-info badge-outline',
    'badge_text' => 'All Services',
    ],
    'flexi' => [
    'title' => 'Flexiload History',
    'subtitle' => 'Only Flexiload history is shown here.',
    'badge_class' => 'badge-secondary',
    'badge_text' => 'Only Flexiload Records',
    ],
    'internet' => [
    'title' => 'Internet Pack History',
    'subtitle' => 'Only Internet Pack history is shown here.',
    'badge_class' => 'badge-primary',
    'badge_text' => 'Only Internet Pack Records',
    ],
    'bkash' => [
    'title' => 'Bkash History',
    'subtitle' => 'Approved Bkash balance add history is shown here.',
    'badge_class' => 'badge-info',
    'badge_text' => 'Only Bkash Records',
    ],
    'nagad' => [
    'title' => 'Nagad History',
    'subtitle' => 'Approved Nagad balance add history is shown here.',
    'badge_class' => 'badge-success',
    'badge_text' => 'Only Nagad Records',
    ],
    'rocket' => [
    'title' => 'Rocket History',
    'subtitle' => 'Approved Rocket balance add history is shown here.',
    'badge_class' => 'badge-warning',
    'badge_text' => 'Only Rocket Records',
    ],
    'upay' => [
    'title' => 'Upay History',
    'subtitle' => 'Approved Upay balance add history is shown here.',
    'badge_class' => 'badge-accent',
    'badge_text' => 'Only Upay Records',
    ],
    ];
    $activeHistoryType = array_key_exists(($historyType ?? 'all'), $historyMeta) ? ($historyType ?? 'all') : 'all';
    $historyRouteParams = $activeHistoryType === 'all' ? [] : ['type' => $activeHistoryType];
    $historyCount = $history->count();
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
                            <span class="font-bold">৳ {{ number_format($user->main_bal ?? 0, 2) }}</span>
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
                            <h1 class="text-3xl font-bold">{{ $historyMeta[$activeHistoryType]['title'] }}</h1>
                            <p class="text-base-content/60">{{ $historyMeta[$activeHistoryType]['subtitle'] }}</p>
                        </div>
                        <a href="{{ route('dashboard') }}" class="btn btn-ghost">← Back to Dashboard</a>
                    </div>

                    <div class="card bg-base-100 shadow-md mb-6">
                        <div class="card-body gap-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold">History Filter</h2>
                                    <p class="text-sm text-base-content/70">Defaultভাবে আজকের history দেখাচ্ছে। পুরনো data দেখতে date filter use করুন।</p>
                                </div>
                                <div class="badge {{ $historyMeta[$activeHistoryType]['badge_class'] }} badge-lg">{{ $historyMeta[$activeHistoryType]['badge_text'] }}</div>
                            </div>
                            <form method="GET" action="{{ route('user.all.history') }}">
                                @if($activeHistoryType !== 'all')
                                <input type="hidden" name="type" value="{{ $activeHistoryType }}" />
                                @endif
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Date From</span></label>
                                        <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="input input-bordered" />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Date To</span></label>
                                        <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="input input-bordered" />
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('user.all.history', $historyRouteParams) }}" class="btn btn-ghost">Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra">
                                    <thead>
                                        <tr>
                                            <th>SL</th>
                                            <th>Type</th>
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
                                            <td>{{ $historyCount - $loop->index }}</td>
                                            <td>
                                                @if(($item->type ?? 'drive') === 'drive')
                                                <span class="badge badge-info">Drive</span>
                                                @elseif(($item->type ?? '') === 'flexi')
                                                <span class="badge badge-secondary">Flexi</span>
                                                @elseif(($item->type ?? '') === 'bkash')
                                                <span class="badge badge-info">Bkash</span>
                                                @elseif(($item->type ?? '') === 'nagad')
                                                <span class="badge badge-success">Nagad</span>
                                                @elseif(($item->type ?? '') === 'rocket')
                                                <span class="badge badge-warning">Rocket</span>
                                                @elseif(($item->type ?? '') === 'upay')
                                                <span class="badge badge-accent">Upay</span>
                                                @else
                                                <span class="badge badge-primary">Internet</span>
                                                @endif
                                            </td>
                                            <td><span class="badge badge-primary">{{ $item->operator }}</span></td>
                                            <td>{{ $item->mobile }}</td>
                                            <td>৳{{ number_format($item->amount, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $item->status == 'success' ? 'badge-success' : (($item->status ?? '') == 'cancelled' ? 'badge-warning' : 'badge-error') }}">
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
                    <a href="{{ route('user.add.balance') }}">
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
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>New Request</span></summary>
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
                    <a href="{{ route('user.pending.requests') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Pending Request
                    </a>
                </li>
                @endif
                @if($canAllHistory || $canDriveHistory)
                <li>
                    <details open>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5M21 21v-5h-5M4 4l16 16" />
                                </svg>History</span></summary>
                        <ul class="p-2">
                            @if($canAllHistory)
                            <li><a class="{{ $activeHistoryType === 'all' ? 'active bg-primary text-primary-content' : '' }}" href="{{ route('user.all.history') }}">All history</a></li>
                            <li><a class="{{ $activeHistoryType === 'flexi' ? 'active bg-primary text-primary-content' : '' }}" href="{{ route('user.all.history', ['type' => 'flexi']) }}">Flexiload</a></li>
                            <li><a class="{{ $activeHistoryType === 'bkash' ? 'active bg-primary text-primary-content' : '' }}" href="{{ route('user.all.history', ['type' => 'bkash']) }}">Bkash</a></li>
                            <li><a class="{{ $activeHistoryType === 'nagad' ? 'active bg-primary text-primary-content' : '' }}" href="{{ route('user.all.history', ['type' => 'nagad']) }}">Nagad</a></li>
                            <li><a class="{{ $activeHistoryType === 'rocket' ? 'active bg-primary text-primary-content' : '' }}" href="{{ route('user.all.history', ['type' => 'rocket']) }}">Rocket</a></li>
                            <li><a class="{{ $activeHistoryType === 'upay' ? 'active bg-primary text-primary-content' : '' }}" href="{{ route('user.all.history', ['type' => 'upay']) }}">Upay</a></li>
                            <li><a class="{{ $activeHistoryType === 'internet' ? 'active bg-primary text-primary-content' : '' }}" href="{{ route('user.all.history', ['type' => 'internet']) }}">Internet Pack</a></li>
                            @endif
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
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                                </svg>Prepaid Card</span></summary>
                        <ul class="p-2">
                            <li><a href="#">Buy Card</a></li>
                            <li><a href="#">Card History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14h6m-6 4h6m2 2h-10a2 2 0 01-2-2V7a2 2 0 012-2h10a2 2 0 012 2v11a2 2 0 01-2 2z" />
                                </svg>Bill Pay</span></summary>
                        <ul class="p-2">
                            <li><a href="#">New Bill Pay</a></li>
                            <li><a href="#">History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h16V4M4 20v-5h16v5M4 12h16" />
                                </svg>Internet Bank</span></summary>
                        <ul class="p-2">
                            <li><a href="#">Banking Request</a></li>
                            <li><a href="#">History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8v-4a4 4 0 00-3-3.87" />
                                </svg>SMS</span></summary>
                        <ul class="p-2">
                            <li><a href="#">Send SMS</a></li>
                            <li><a href="#">History</a></li>
                        </ul>
                    </details>
                </li>
                <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87" />
                        </svg>Reseller</a></li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.008-3 2.25S10.343 12.5 12 12.5s3 1.008 3 2.25S13.657 17 12 17m0-9V7m0 10v-1" />
                                </svg>Payment</span></summary>
                        <ul class="p-2">
                            <li><a href="#">Return Found</a></li>
                            <li><a href="#">Payment History</a></li>
                            <li><a href="#">Receive History</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M9 13v6M13 9v10M17 5v14" />
                                </svg>Reports</span></summary>
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
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" stroke-width="2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>My Accounts</span></summary>
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
                <li><a href="{{ route('complaints.index') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>Complain</a></li>
                @endif
                <li>
                    <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full text-left"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</body>

</html>