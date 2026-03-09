<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Logs - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="bg-base-200 min-h-screen">

    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col">

            <div class="navbar bg-base-100 shadow-sm sticky top-0 z-30 px-4">
                <div class="flex-none lg:hidden">
                    <label for="my-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                            <i class="fa-solid fa-shield-halved text-lg"></i>
                        </div>
                        <div>
                            <div class="text-xl font-bold leading-tight">Device Logs</div>
                            <div class="text-xs text-base-content/60">Track approvals, IP changes and login activity</div>
                        </div>
                    </div>
                </div>
                <div class="flex-none gap-4">
                    <div class="flex flex-col items-end hidden sm:flex">
                        <span class="text-sm font-bold leading-none">{{ Auth::user()->name }}</span>
                        <span class="text-xs opacity-50">Administrator</span>
                    </div>
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            @if($user && $user->profile_picture)
                            <div class="w-10 rounded-full">
                                <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Profile" class="w-full h-full object-cover rounded-full">
                            </div>
                            @else
                            <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center font-bold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            @endif
                        </div>
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            <li><a href="{{ route('admin.profile') }}">Profile</a></li>
                            <li><a href="{{ route('admin.homepage.edit') }}">Settings</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="text-error w-full text-left">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <main class="p-4 sm:p-6 flex-grow space-y-6">
                @php($pageLogs = $logs->getCollection())
                @php($approvedCount = $pageLogs->where('status', 'active')->count())
                @php($pendingReviewCount = $pageLogs->where('status', '!=', 'active')->count())

                @if(session('success'))
                <div class="alert alert-success shadow-sm text-sm">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                <section class="rounded-3xl border border-base-300 bg-gradient-to-r from-base-100 to-base-200/80 p-5 sm:p-6 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                <i class="fa-solid fa-lock"></i>
                                Device Logs Overview
                            </div>
                            <h1 class="mt-3 text-2xl font-bold">Device access activity</h1>
                            <p class="mt-1 max-w-2xl text-sm text-base-content/70">
                                Review new device and IP change requests, then approve trusted logins from one place.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <div class="badge badge-outline badge-lg">Total {{ $logs->total() }}</div>
                            <div class="badge badge-success badge-lg text-white">Approved {{ $approvedCount }}</div>
                            <div class="badge badge-warning badge-lg">Pending {{ $pendingReviewCount }}</div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-base-content/60">Total Logs</p>
                                <p class="mt-2 text-3xl font-bold">{{ $logs->total() }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center">
                                <i class="fa-solid fa-list"></i>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-base-content/60">Current Page</p>
                                <p class="mt-2 text-3xl font-bold">{{ $pageLogs->count() }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center">
                                <i class="fa-solid fa-table"></i>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-base-content/60">Approved</p>
                                <p class="mt-2 text-3xl font-bold text-success">{{ $approvedCount }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-base-content/60">Pending Review</p>
                                <p class="mt-2 text-3xl font-bold text-warning">{{ $pendingReviewCount }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                        </div>
                    </div>
                </section>

                <form action="{{ route('admin.device.logs') }}" method="GET" class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <h2 class="text-lg font-bold">Filter device logs</h2>
                            <p class="text-sm text-base-content/60">Narrow the list by reseller and result size.</p>
                        </div>

                        <div class="grid w-full gap-4 md:grid-cols-2 xl:max-w-3xl xl:grid-cols-[140px_minmax(0,1fr)_auto_auto]">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Show</span></label>
                                <select name="per_page" class="select select-bordered w-full">
                                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Reseller Filter</span></label>
                                <select name="reseller" class="select select-bordered w-full">
                                    <option value="">View All Reseller</option>
                                    @foreach($resellers as $reseller)
                                    @php($resellerIdentifier = $reseller->username ?? $reseller->email)
                                    <option value="{{ $resellerIdentifier }}" {{ request('reseller') == $resellerIdentifier ? 'selected' : '' }}>
                                        {{ $reseller->name }} ({{ $resellerIdentifier }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-full xl:w-auto">
                                <i class="fa-solid fa-filter"></i>
                                Apply Filter
                            </button>

                            @if(request()->filled('reseller') || request()->filled('per_page'))
                            <a href="{{ route('admin.device.logs') }}" class="btn btn-ghost w-full xl:w-auto">Reset</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="card overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-base-300 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold">Login activity list</h2>
                            <p class="text-sm text-base-content/60">Pending devices stay blocked until an admin approves them.</p>
                        </div>
                        <div class="badge badge-outline">Showing {{ $pageLogs->count() }} of {{ $logs->total() }}</div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-base-200/70 text-xs uppercase tracking-wide text-base-content/70">
                                <tr>
                                    <th>Nr.</th>
                                    <th>Time</th>
                                    <th>IP Address</th>
                                    <th>User</th>
                                    <th>Browser + OS</th>
                                    <th>2-Step</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $key => $log)
                                <tr class="border-t border-base-200 {{ $log->status === 'active' ? 'bg-white hover:bg-base-100' : 'bg-amber-50/60 hover:bg-amber-50' }}">
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-base-200 text-sm font-bold">
                                                {{ $logs->firstItem() + $key }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-xs">
                                        <div class="font-semibold text-sm text-base-content">{{ $log->created_at->format('d M Y') }}</div>
                                        <div class="text-base-content/60">{{ $log->created_at->format('h:i A') }}</div>
                                        <div class="mt-1 text-base-content/50">Last seen: {{ $log->updated_at?->format('d M Y h:i A') }}</div>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center rounded-full bg-info/10 px-3 py-1 font-mono text-sm text-info">
                                            {{ $log->ip_address }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-3 min-w-[220px]">
                                            <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                                                {{ strtoupper(substr($log->username, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-sm break-all">{{ $log->username }}</div>
                                                <div class="text-xs text-base-content/60">
                                                    {{ $log->status === 'active' ? 'Trusted device access' : 'Waiting for manual approval' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="max-w-xs">
                                        <div class="rounded-2xl bg-base-200/70 px-3 py-2 text-xs leading-5 break-words">
                                            {{ $log->browser_os }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($log->two_step_verified)
                                        <span class="badge badge-success">On</span>
                                        @else
                                        <span class="badge badge-error">Off</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $log->status == 'active' ? 'badge-success text-white' : 'badge-warning text-slate-900' }} gap-1">
                                            <i class="fa-solid {{ $log->status == 'active' ? 'fa-circle-check' : 'fa-hourglass-half' }} text-[10px]"></i>
                                            {{ $log->status == 'active' ? 'Approved' : 'Pending Approval' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2 min-w-[160px]">
                                            @if($log->status !== 'active')
                                            <form action="{{ route('admin.device.logs.approve', $log->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm text-white">
                                                    <i class="fa-solid fa-shield-check"></i>
                                                    Approve Device
                                                </button>
                                            </form>
                                            @endif

                                            <form action="{{ route('admin.device.logs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-ghost btn-sm text-error">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="py-14">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-base-200 text-base-content/60">
                                                <i class="fa-solid fa-laptop-file text-xl"></i>
                                            </div>
                                            <div class="text-lg font-semibold">No logs found</div>
                                            <p class="mt-1 max-w-md text-sm text-base-content/60">Try adjusting the reseller filter or reduce the result limit to find a specific device record.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    {{ $logs->appends(request()->query())->links() }}
                </div>

            </main>

            <footer class="footer footer-center p-4 bg-base-100 text-base-content border-t border-base-300">
                <aside>
                    <p class="text-sm">Copyright © 2026 - All rights reserved by <b>{{ $settings->company_name ?? 'Codecartel Telecom' }}</b></p>
                </aside>
            </footer>
        </div>

        <aside class="drawer-side z-40">
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
                        <a href="{{ route('admin.dashboard') }}">
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
                                <li><a>Flexiload</a></li>
                                <li><a href="{{ route('admin.drive.history') }}">Drive</a></li>
                                <li><a href="{{ route('admin.internet.history') }}">Internet Pack</a></li>
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
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                Offer Settings
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.operator.create') }}">Add Operator</a></li>
                                <li><a href="{{ route('admin.regular.offer') }}">Regular Package</a></li>
                                <li><a href="{{ route('admin.drive.offer') }}">Drive Package</a></li>
                            </ul>
                        </details>
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
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                </svg>
                                Administration
                            </summary>
                            <ul>
                                <li><a>Service Modules</a></li>
                                <li><a>Rate Modules</a></li>
                                <li><a>Deposit</a></li>
                                <li><a>Modem List</a></li>
                                <li><a>Modem Device</a></li>
                                <li><a>Recharge Block List</a></li>
                                <li><a href="{{ route('api.index') }}">Api Settings</a></li>
                                <li><a href="{{ route('admin.payment.gateway') }}">Payment Gateway</a></li>
                                <li><a>Security Settings</a></li>
                                <li><a>Deleted Accounts</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details open>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Tools
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.branding') }}">Branding</a></li>
                                <li><a href="{{ route('admin.device.logs') }}" class="active bg-primary text-primary-content">Device Logs</a></li>
                                <li><a>Reseller Notice</a></li>
                                <li><a>Login Notice</a></li>
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
                                <li><a href="/admin/manage-admins">Manage Admin Users</a></li>
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
                            </ul>
                        </details>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left text-error">
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
        </aside>
    </div>

</body>

</html>