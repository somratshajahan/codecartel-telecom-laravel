<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Manage Drive Package' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
            <!-- Navbar -->
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost drawer-button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Manage Drive Package ({{ $operator }})</a></div>
                <div class="flex-none gap-2">
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
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
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
                <h1 class="text-3xl font-bold mb-6">Manage Drive Package - {{ $operator }}</h1>

                <!-- Search -->
                <div class="card bg-base-100 shadow-xl mb-6">
                    <div class="card-body">
                        <h3 class="text-lg font-bold mb-4">Search</h3>
                        <form method="GET" action="{{ route('admin.manage.drive.package', ['operator' => $operator]) }}">
                            <div class="flex gap-2">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="eg: 100 or package name" class="input input-bordered flex-1" />
                                <button type="submit" class="btn btn-primary">Search</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.manage.drive.package.update', ['operator' => $operator]) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-info">Update list from API</button>
                        </form>
                    </div>
                </div>

                <!-- Add Package -->
                <div class="card bg-base-100 shadow-xl mb-6">
                    <div class="card-body">
                        <h3 class="text-lg font-bold mb-4">Add Package</h3>
                        <form method="POST" action="{{ route('admin.manage.drive.package.store', ['operator' => $operator]) }}">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="label"><span class="label-text font-semibold">Package Name</span></label>
                                    <input type="text" name="package_name" placeholder="e.g. 50GB + 1000 min" class="input input-bordered w-full required" required />
                                </div>
                                <div>
                                    <label class="label"><span class="label-text font-semibold">Price</span></label>
                                    <input type="number" name="price" placeholder="0.00" class="input input-bordered w-full required" required />
                                </div>
                                <div>
                                    <label class="label"><span class="label-text font-semibold">Commission</span></label>
                                    <input type="number" name="commission" placeholder="0.00" class="input input-bordered w-full" required />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                <div>
                                    <label class="label"><span class="label-text font-semibold">Expire Date</span></label>
                                    <input type="date" name="expire" class="input input-bordered w-full" required />
                                </div>
                                <div>
                                    <label class="label"><span class="label-text font-semibold">Status</span></label>
                                    <select name="status" class="select select-bordered w-full">
                                        <option value="active" selected>Active</option>
                                        <option value="deactive">Deactive</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary w-full">Add Package</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Package List -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h3 class="text-lg font-bold mb-4">Package List</h3>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="checkbox" id="select-all" /></th>
                                        <th>ID</th>
                                        <th>Operator</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Commission</th>
                                        <th>Selling Price</th>
                                        <th>Expire</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($packages ?? [] as $package)
                                    <tr>
                                        <td><input type="checkbox" class="checkbox package-checkbox" name="package_ids[]" value="{{ $package->id }}" /></td>
                                        <td>{{ $package->id }}</td>
                                        <td><span class="badge badge-primary">{{ $package->operator }}</span></td>
                                        <td class="max-w-xs truncate">{{ $package->name }}</td>
                                        <td>৳{{ $package->price }}</td>
                                        <td>৳{{ $package->commission }}</td>
                                        <td>৳{{ $package->price - $package->commission }}</td>
                                        <td>{{ $package->expire->format('Y-m-d') }}</td>
                                        <td><span class="badge {{ $package->status == 'active' ? 'badge-success' : 'badge-error' }}">{{ ucfirst($package->status) }}</span></td>
                                        <td>
                                            <a href="{{ route('admin.manage.drive.package.edit', ['operator' => $package->operator, 'id' => $package->id]) }}" class="btn btn-sm btn-warning">Edit</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No packages found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Bulk Action -->
                        <div class="mt-4 p-4 bg-base-200 rounded-lg">
                            <div class="flex gap-2 items-center">
                                <span class="font-bold">Bulk Action:</span>
                                <select name="bulk_action" id="bulk-action-select" class="select select-bordered select-sm">
                                    <option value="">Select</option>
                                    <option value="active">Active</option>
                                    <option value="deactive">Deactive</option>
                                    <option value="delete">Delete</option>
                                    <option value="Blank">Create Blank Package</option>
                                </select>
                                <button type="button" id="apply-bulk-action" class="btn btn-primary btn-sm">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar - Full Menu -->
        <div class="drawer-side z-40 overflow-y-auto">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 h-screen border-r border-base-200 transition-all duration-300 overflow-y-auto">
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
                    <li><a href="{{ route('admin.dashboard') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>Dashboard</a></li>
                    <li><a href="{{ route('admin.backup') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>Backup</a></li>
                    <li><a href="{{ route('admin.pending.drive.requests') }}"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7l4-4m0 0l4 4m-4-4v18" />
                            </svg>Pending Request</a></li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>Recharge History</summary>
                            <ul>
                                <li><a href="{{ route('admin.all.history') }}">All History</a></li>
                                <li><a>Flexiload</a></li>
                                <li><a href="{{ route('admin.drive.history') }}">Drive</a></li>
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
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>Message Inbox</summary>
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
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>Offer Settings</summary>
                            <ul>
                                <li><a href="{{ route('admin.regular.offer') }}">Regular Package</a></li>
                                <li><a href="{{ route('admin.drive.offer') }}">Drive Package</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>Card Management</summary>
                            <ul>
                                <li><a>Card Add</a></li>
                                <li><a>Card History</a></li>
                                <li><a>Card Management</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>Bill Pay</summary>
                            <ul>
                                <li><a>Pending Request</a></li>
                                <li><a>Bill Pay History</a></li>
                                <li><a>Bill Pay Settings</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>Banking</summary>
                            <ul>
                                <li><a>Pending Request</a></li>
                                <li><a>Bank History</a></li>
                                <li><a>Bank Settings</a></li>
                            </ul>
                        </details>
                    </li>
                    <li><a><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4" />
                            </svg>Sub Admin</a></li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>Reseller</summary>
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
                    <li><a><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>Payment History</a></li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>Reports</summary>
                            <ul>
                                <li><a>Receive reports</a></li>
                                <li><a>Balance Reports</a></li>
                                <li><a>Operator Reports</a></li>
                                <li><a>Daily Reports</a></li>
                                <li><a>Total usages</a></li>
                                <li><a>Transaction</a></li>
                                <li><a>Trnx ID</a></li>
                                <li><a>Sales Report</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                </svg>Administration</summary>
                            <ul>
                                <li><a>Service Modules</a></li>
                                <li><a>Rate Modules</a></li>
                                <li><a>Deposit</a></li>
                                <li><a>Modem List</a></li>
                                <li><a>Modem Device</a></li>
                                <li><a>Recharge Block List</a></li>
                                <li><a>Api Settings</a></li>
                                <li><a href="{{ route('admin.payment.gateway') }}">Payment Gateway</a></li>
                                <li><a>Security Settings</a></li>
                                <li><a>Deleted Accounts</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>Tools</summary>
                            <ul>
                                <li><a href="{{ route('admin.branding') }}">Branding</a></li>
                                <li><a href="{{ route('admin.device.logs') }}">Device Logs</a></li>
                                <li><a>Reseller Notice</a></li>
                                <li><a>Login Notice</a></li>
                                <li><a>Slides</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2h1a2 2 0 002-2v-1a2 2 0 012-2h1.945M7.757 15.757a3 3 0 104.486 0M12 10.5a3 3 0 110-6 3 3 0 010 6z" />
                                </svg>Global</summary>
                            <ul>
                                <li><a>Country</a></li>
                                <li><a>Operator</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>Admin Account</summary>
                            <ul>
                                <li><a href="{{ route('admin.profile') }}">My Profile</a></li>
                                <li><a href="{{ route('admin.manage.admins') }}">Manage Admin Users</a></li>
                                <li><a href="{{ route('admin.change.credentials') }}">Change Password & PIN</a></li>
                            </ul>
                        </details>
                    </li>
                    <li><a><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>Complain</a></li>
                    <li><a><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>Support</a></li>
                    <li>
                        <details>
                            <summary><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>Settings</summary>
                            <ul>
                                <li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li>
                                <li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li>
                                <li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li>
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
            </aside>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const drawerToggle = document.getElementById('my-drawer');
                const drawerButton = document.querySelector('label[for="my-drawer"]');

                const setDrawerDefault = () => {
                    if (!drawerToggle) return;
                    drawerToggle.checked = window.innerWidth >= 1024;
                };

                setDrawerDefault();
                window.addEventListener('resize', setDrawerDefault);

                if (drawerButton && drawerToggle) {
                    drawerButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        drawerToggle.checked = !drawerToggle.checked;
                    });
                }

                const selectAll = document.getElementById('select-all');
                const packageCheckboxes = document.querySelectorAll('.package-checkbox');

                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        packageCheckboxes.forEach(checkbox => {
                            checkbox.checked = selectAll.checked;
                        });
                    });
                }

                const applyButton = document.getElementById('apply-bulk-action');
                const bulkActionSelect = document.getElementById('bulk-action-select');

                if (applyButton) {
                    applyButton.addEventListener('click', function() {
                        const selectedCheckboxes = document.querySelectorAll('.package-checkbox:checked');
                        const action = bulkActionSelect.value;

                        if (!action) {
                            alert('Please select an action');
                            return;
                        }

                        if (selectedCheckboxes.length === 0) {
                            alert('Please select at least one package');
                            return;
                        }

                        let confirmMessage = '';
                        if (action === 'delete') {
                            confirmMessage = `Are you sure you want to delete ${selectedCheckboxes.length} package(s)?`;
                        } else if (action === 'active') {
                            confirmMessage = `Are you sure you want to activate ${selectedCheckboxes.length} package(s)?`;
                        } else if (action === 'deactive') {
                            confirmMessage = `Are you sure you want to deactivate ${selectedCheckboxes.length} package(s)?`;
                        }

                        if (confirm(confirmMessage)) {
                            selectedCheckboxes.forEach(checkbox => {
                                const row = checkbox.closest('tr');
                                const statusBadge = row.querySelector('td:nth-child(8) .badge');

                                if (action === 'delete') {
                                    row.remove();
                                } else if (action === 'active') {
                                    statusBadge.textContent = 'Active';
                                    statusBadge.classList.remove('badge-error');
                                    statusBadge.classList.add('badge-success');
                                } else if (action === 'deactive') {
                                    statusBadge.textContent = 'Deactive';
                                    statusBadge.classList.remove('badge-success');
                                    statusBadge.classList.add('badge-error');
                                }
                            });

                            packageCheckboxes.forEach(checkbox => checkbox.checked = false);
                            if (selectAll) selectAll.checked = false;
                            bulkActionSelect.value = '';
                        }
                    });
                }
            });
        </script>
</body>

</html>