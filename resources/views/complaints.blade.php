<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Complaints' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-base-200">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        
        <!-- Main Content -->
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
                <div class="flex-none gap-2">
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="font-bold">৳ {{ number_format($user->main_bal ?? 0, 2) }}</span>
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
                            <li><a href="{{ route('user.profile') }}">Profile</a></li>
                            <li><a>Settings</a></li>
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

            <!-- Main Content Area -->
            <main class="flex-1 p-6">
                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-3xl font-bold">Complaints System</h1>
                        <p class="text-base-content/60">Submit and track your complaints</p>
                    </div>
                    <button onclick="document.getElementById('complaintModal').showModal()" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        New Complaint
                    </button>
                </div>

                <!-- Success/Error Messages -->
                @if(session('success'))
                <div class="alert alert-success mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-error mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                <!-- Filter Card -->
                <div class="card bg-base-100 shadow-lg mb-6">
                    <div class="card-body p-4">
                        <form method="GET" action="{{ route('complaints.index') }}" class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[120px]">
                                <label class="label py-1"><span class="label-text text-sm font-medium">ID#</span></label>
                                <input type="text" name="complaint_id" value="{{ request('complaint_id') }}" class="input input-bordered input-sm w-full" placeholder="Complaint ID">
                            </div>
                            <div class="flex-1 min-w-[180px]">
                                <label class="label py-1"><span class="label-text text-sm font-medium">Search</span></label>
                                <input type="text" name="search" value="{{ request('search') }}" class="input input-bordered input-sm w-full" placeholder="Search subject or message...">
                            </div>
                            <div class="flex-1 min-w-[140px]">
                                <label class="label py-1"><span class="label-text text-sm font-medium">Status</span></label>
                                <select name="status" class="select select-bordered select-sm w-full">
                                    <option value="--Any--">--Any--</option>
                                    <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
                                    <option value="Answered" {{ request('status') == 'Answered' ? 'selected' : '' }}>Answered</option>
                                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Close" {{ request('status') == 'Close' ? 'selected' : '' }}>Close</option>
                                </select>
                            </div>
                            <div class="flex-none">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Filter
                                </button>
                                @if(request()->has('complaint_id') || request()->has('search') || (request()->has('status') && request('status') != '--Any--'))
                                    <a href="{{ route('complaints.index') }}" class="btn btn-ghost btn-sm">Clear</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Complaints Table -->
                <div class="card bg-base-100 shadow-lg">
                    <div class="card-body p-0">
                        @if($complaints->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="table table-zebra">
                                    <thead>
                                        <tr>
                                            <th class="w-16">ID</th>
                                            <th>Subject</th>
                                            <th>Message</th>
                                            <th class="w-24">Status</th>
                                            <th class="w-24">Reply</th>
                                            <th class="w-32">Created</th>
                                            <th class="w-32">Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($complaints as $complaint)
                                        <tr class="hover">
                                            <td class="font-mono text-sm">#{{ $complaint->id }}</td>
                                            <td class="font-medium">{{ $complaint->subject }}</td>
                                            <td class="max-w-xs truncate" title="{{ $complaint->message }}">{{ Str::limit($complaint->message, 50) }}</td>
                                            <td>
                                                @if($complaint->status == 'Open')
                                                    <span class="badge badge-info">Open</span>
                                                @elseif($complaint->status == 'Answered')
                                                    <span class="badge badge-success">Answered</span>
                                                @elseif($complaint->status == 'In Progress')
                                                    <span class="badge badge-warning">In Progress</span>
                                                @else
                                                    <span class="badge badge-neutral">Close</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($complaint->reply)
                                                    <span class="text-success">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="text-base-content/40">-</span>
                                                @endif
                                            </td>
                                            <td class="text-sm">{{ \Carbon\Carbon::parse($complaint->created_at)->format('d M Y') }}</td>
                                            <td class="text-sm">{{ \Carbon\Carbon::parse($complaint->updated_at)->format('d M Y') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-12">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-base-content/60 text-lg">No complaints found</p>
                                <p class="text-base-content/40 text-sm mt-1">Click "New Complaint" to submit your first complaint</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer -->
                <footer class="footer items-center p-4 bg-base-300 text-base-content mt-8">
                    <div class="items-center grid-flow-col">
                        <p>Copyright © 2026 - All right reserved by {{ optional($settings)->company_name ?? 'Codecartel Telecom' }} | Version 1.0.0</p>
                    </div>
                </footer>
            </main>
        </div>

        <!-- Sidebar -->
        <div class="drawer-side">
            <label for="my-drawer" class="drawer-overlay"></label>
            <ul class="menu p-4 w-60 min-h-full bg-base-100 text-base-content">
                <li>
                    <a href="{{ route('dashboard') }}" class="py-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <details>
                        <summary class="py-2">
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                New Request
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="#">Flexiload</a></li>
                            <li><a href="{{ route('user.internet') }}">Internet Pack</a></li>
                            <li><a href="{{ route('user.drive') }}">Drive</a></li>
                            <li><a href="#">Bkash</a></li>
                            <li><a href="#">Nagad</a></li>
                            <li><a href="#">Rocket</a></li>
                            <li><a href="#">Upay</a></li>
                            <li><a href="#">Islami Bank</a></li>
                            <li><a href="#">Bulk Flexi</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <a href="{{ route('user.pending.requests') }}" class="flex items-center justify-between py-2">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                        <summary class="py-2">
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5M21 21v-5h-5M4 4l16 16" />
                                </svg>
                                History
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="#">All history</a></li>
                            <li><a href="#">Flexiload</a></li>
                            <li><a href="#">Internet Pack</a></li>
                            <li><a href="{{ route('user.drive.history') }}">Drive</a></li>
                            <li><a href="#">Bkash</a></li>
                            <li><a href="#">Nagad</a></li>
                            <li><a href="#">Rocket</a></li>
                            <li><a href="#">Upay</a></li>
                            <li><a href="#">Islami Bank</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary class="py-2">
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
                        <summary class="py-2">
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
                        <summary class="py-2">
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
                        <summary class="py-2">
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8v-4a4 4 0 00-3-3.87M9 13v6M13 9v10M17 5v14" />
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
                    <a href="#" class="py-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87" />
                        </svg>
                        Reseller
                    </a>
                </li>
                <li>
                    <details>
                        <summary class="py-2">
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
                        <summary class="py-2">
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
                        <summary class="py-2">
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
                            <li><a href="#">Google OTP</a></li>
                            <li><a href="#">Email/Mobile OTP</a></li>
                            <li><a href="#">API</a></li>
                            <li><a href="#">My Rates</a></li>
                            <li><a href="#">Access Log</a></li>
                            <li><a href="#">Reseller Device logs</a></li>
                            <li><a href="#">Change pin</a></li>
                            <li><a href="#">Change Password</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <a class="active bg-primary text-primary-content py-2" href="{{ route('complaints.index') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Complain
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full text-left py-2">
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

    <!-- New Complaint Modal -->
    <dialog id="complaintModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Submit New Complaint</h3>
            <form method="POST" action="{{ route('complaints.store') }}">
                @csrf
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text font-medium">Subject</span>
                    </label>
                    <input type="text" name="subject" placeholder="Enter complaint subject" class="input input-bordered w-full" required />
                </div>
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text font-medium">Message</span>
                    </label>
                    <textarea name="message" placeholder="Describe your issue in detail..." class="textarea textarea-bordered w-full" rows="4" required></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="document.getElementById('complaintModal').close()" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Complaint</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</body>
</html>

