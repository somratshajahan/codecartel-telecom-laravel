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
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-base-200">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost drawer-button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </label>
                </div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Admin</a></div>
                <div class="flex-none gap-2">
                    <button class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    </button>
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar"><div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">A</div></div>
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            <li><a>Profile</a></li>
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
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $item->user->name ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($item->service === 'drive')
                                                            <span class="badge badge-info">Drive</span>
                                                        @elseif($item->service === 'bkash')
                                                            <span class="badge badge-warning">Bkash</span>
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
                                                    <td colspan="9" class="text-center">No history found</td>
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
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>ID</th>
                                                <th>User</th>
                                                <th>Operator</th>
                                                <th>Package</th>
                                                <th>Mobile</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($requests as $request)
                                                <tr>
                                                    <td><span class="badge badge-info">{{ $request->request_type ?? 'Drive' }}</span></td>
                                                    <td>{{ $request->id }}</td>
                                                    <td>{{ $request->user->name }}</td>
                                                    <td><span class="badge badge-primary">{{ $request->operator }}</span></td>
                                                    <td>{{ $request->package->name ?? 'N/A' }}</td>
                                                    <td>{{ $request->mobile }}</td>
                                                    <td>৳{{ number_format($request->amount, 2) }}</td>
                                                    <td><span class="badge badge-warning">{{ ucfirst($request->status) }}</span></td>
                                                    <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                                                    <td>
                                                        @if(($request->request_type ?? 'Drive') === 'Drive')
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
                                                        @else
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
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center">No pending requests</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
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
                                    <select class="select select-bordered select-sm"><option>25</option><option>50</option><option>100</option></select>
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

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-xs">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Password</th>
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
                                @foreach($users as $u)
                                    <tr>
                                        <td>{{ $u->id }}</td>
                                        <td>{{ $u->username ?? $u->email }}</td>
                                        <td>{{ $u->name }}</td>
                                        <td><button class="btn btn-xs bg-blue-500 hover:bg-blue-600 border-0 text-white">Change</button></td>
                                        <td>{{ $u->main_bal ?? '0.00' }}</td>
                                        <td>{{ $u->bank_bal ?? '0.00' }}</td>
                                        <td>{{ $u->drive_bal ?? '0.00' }}</td>
                                        <td>{{ $u->stock ?? '0.00' }}</td>
                                        <td>{{ $u->last_login ?? $u->updated_at }}</td>
                                        <td><a href="{{ route('admin.resellers', ['level' => $u->level]) }}" class="link link-primary">{{ ucfirst($u->level ?? '-') }}</a></td>
                                        <td>{{ $u->otp ?? '' }}</td>
                                        <td>{{ $u->created_at }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.resellers.toggle', $u) }}" class="inline">@csrf
                                                <button type="submit" class="px-3 py-1 rounded {{ $u->is_active ? 'bg-blue-500 text-white' : 'bg-red-500 text-white' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</button>
                                            </form>
                                        </td>
                                        <td>{{ $u->parent ? $u->parent->name : 'Self' }}</td>
<td><a href="{{ route('admin.add.balance', $u->id) }}" class="btn btn-xs bg-blue-500 hover:bg-blue-600 border-0 text-white px-3 py-1">Add Balance</a></td>
                                    </tr>
                                @endforeach
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
</a></li>
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
                    <li>
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Payment History
                        </a>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Reports
                            </summary>
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
                                <li><a>Api Settings</a></li>
                                <li><a>Payment Getway</a></li>
                                <li><a>Security Settings</a></li>
                                <li><a>Deleted Accounts</a></li>
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
                                <li><a>Branding</a></li>
                                <li><a>Device Logs</a></li>
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
                                <li><a>Google OTP</a></li>
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
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="add_user_modal.close()" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
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
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        @if(!isset($users))
        // Recharge History Chart
        const rechargeCtx = document.getElementById('rechargeChart');
        if (rechargeCtx) {
            new Chart(rechargeCtx, {
                type: 'bar',
                data: {
                    labels: ['Yesterday', 'Today'],
                    datasets: [{
                        label: 'Recharge Amount (৳)',
                        data: [{{ $yesterday ?? 0 }}, {{ $today ?? 0 }}],
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
        }

        // Balance Add Chart
        const balanceCtx = document.getElementById('balanceChart');
        if (balanceCtx) {
            new Chart(balanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Yesterday', 'Today'],
                    datasets: [{
                        label: 'Balance Added (৳)',
                        data: [{{ $balanceYesterday ?? 0 }}, {{ $balanceToday ?? 0 }}],
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
        }

        // Operator Sales Pie Chart
        const operatorCtx = document.getElementById('operatorChart');
        if (operatorCtx) {
            const operatorLabels = {!! json_encode($operatorSales->pluck('type')) !!};
            const operatorData = {!! json_encode($operatorSales->pluck('total')) !!};
            
            new Chart(operatorCtx, {
                type: 'pie',
                data: {
                    labels: operatorLabels.length > 0 ? operatorLabels : ['No Data'],
                    datasets: [{
                        data: operatorData.length > 0 ? operatorData : [1],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(200, 200, 200, 0.7)'
                        ],
                        borderWidth: 2
                    }]
                },
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
        }

        // Mobile Banking Pie Chart
        const bankingCtx = document.getElementById('bankingChart');
        if (bankingCtx) {
            const bankingLabels = {!! json_encode($bankingSales->pluck('type')) !!};
            const bankingData = {!! json_encode($bankingSales->pluck('total')) !!};
            
            new Chart(bankingCtx, {
                type: 'pie',
                data: {
                    labels: bankingLabels.length > 0 ? bankingLabels : ['No Data'],
                    datasets: [{
                        data: bankingData.length > 0 ? bankingData : [1],
                        backgroundColor: [
                            'rgba(220, 38, 38, 0.7)',
                            'rgba(251, 146, 60, 0.7)',
                            'rgba(34, 197, 94, 0.7)',
                            'rgba(168, 85, 247, 0.7)',
                            'rgba(200, 200, 200, 0.7)'
                        ],
                        borderWidth: 2
                    }]
                },
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
        }
        @endif
    </script>
</body>
</html>
