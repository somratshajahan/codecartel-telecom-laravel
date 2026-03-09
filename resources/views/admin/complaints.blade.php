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
                        @if(Auth::user() && Auth::user()->profile_picture)
                            <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                                <div class="w-10 rounded-full">
                                    <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="Profile" class="w-full h-full object-cover rounded-full">
                                </div>
                            </div>
                        @else
                            <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar"><div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div></div>
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
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold">Complaints Management</h1>
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
                        <form method="GET" action="{{ route('admin.complaints') }}" class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[120px]">
                                <label class="label py-1"><span class="label-text text-sm font-medium">ID#</span></label>
                                <input type="text" name="complaint_id" value="{{ request('complaint_id') }}" class="input input-bordered input-sm w-full" placeholder="Complaint ID">
                            </div>
                            <div class="flex-1 min-w-[180px]">
                                <label class="label py-1"><span class="label-text text-sm font-medium">Search</span></label>
                                <input type="text" name="search" value="{{ request('search') }}" class="input input-bordered input-sm w-full" placeholder="Search subject, message or email...">
                            </div>
                            <div class="flex-1 min-w-[140px]">
                                <label class="label py-1"><span class="label-text text-sm font-medium">Status</span></label>
                                <select name="status" class="select select-bordered select-sm w-full">
                                    <option value="--Any--">--Any--</option>
                                    <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
                                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Answered" {{ request('status') == 'Answered' ? 'selected' : '' }}>Answered</option>
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
                                    <a href="{{ route('admin.complaints') }}" class="btn btn-ghost btn-sm">Clear</a>
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
                                            <th>User Email</th>
                                            <th>Subject</th>
                                            <th>Message</th>
                                            <th class="w-24">Status</th>
                                            <th>Reply</th>
                                            <th class="w-32">Created</th>
                                            <th class="w-32">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($complaints as $complaint)
                                        <tr class="hover">
                                            <td class="font-mono text-sm">#{{ $complaint->id }}</td>
                                            <td class="text-sm">{{ $complaint->sender_email }}</td>
                                            <td class="font-medium">{{ $complaint->subject }}</td>
                                            <td class="max-w-xs truncate" title="{{ $complaint->message }}">{{ Str::limit($complaint->message, 50) }}</td>
                                            <td>
                                                @if($complaint->status == 'Open')
                                                    <span class="badge badge-info">Open</span>
                                                @elseif($complaint->status == 'In Progress')
                                                    <span class="badge badge-warning">In Progress</span>
                                                @elseif($complaint->status == 'Answered')
                                                    <span class="badge badge-success">Answered</span>
                                                @else
                                                    <span class="badge badge-neutral">Close</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($complaint->reply)
                                                    <span class="text-sm">{{ Str::limit($complaint->reply, 30) }}</span>
                                                @else
                                                    <span class="text-base-content/40">-</span>
                                                @endif
                                            </td>
                                            <td class="text-sm">{{ \Carbon\Carbon::parse($complaint->created_at)->format('d M Y') }}</td>
                                            <td>
                                                <button onclick="document.getElementById('replyModal{{ $complaint->id }}').showModal()" class="btn btn-primary btn-sm">
                                                    Reply
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Reply Modal for each complaint -->
                                        <dialog id="replyModal{{ $complaint->id }}" class="modal">
                                            <div class="modal-box max-w-2xl">
                                                <h3 class="font-bold text-lg mb-2">Complaint #{{ $complaint->id }}</h3>
                                                <div class="bg-base-200 rounded-lg p-4 mb-4">
                                                    <p class="text-sm"><strong>From:</strong> {{ $complaint->sender_email }}</p>
                                                    <p class="text-sm"><strong>Subject:</strong> {{ $complaint->subject }}</p>
                                                    <p class="text-sm mt-2"><strong>Message:</strong></p>
                                                    <p class="text-base-content/80">{{ $complaint->message }}</p>
                                                </div>
                                                
                                                <form method="POST" action="{{ route('admin.complaints.reply', $complaint->id) }}">
                                                    @csrf
                                                    <div class="form-control mb-4">
                                                        <label class="label">
                                                            <span class="label-text font-medium">Reply</span>
                                                        </label>
                                                        <textarea name="reply" class="textarea textarea-bordered w-full" rows="4" placeholder="Write your reply..." required>{{ $complaint->reply ?? '' }}</textarea>
                                                    </div>
                                                    <div class="form-control mb-4">
                                                        <label class="label">
                                                            <span class="label-text font-medium">Status</span>
                                                        </label>
                                                        <select name="status" class="select select-bordered w-full">
                                                            <option value="Open" {{ $complaint->status == 'Open' ? 'selected' : '' }}>Open</option>
                                                            <option value="In Progress" {{ $complaint->status == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                                            <option value="Answered" {{ $complaint->status == 'Answered' ? 'selected' : '' }}>Answered</option>
                                                            <option value="Close" {{ $complaint->status == 'Close' ? 'selected' : '' }}>Close</option>
                                                        </select>
                                                    </div>
                                                    <div class="flex justify-end gap-2 mt-6">
                                                        <button type="button" onclick="document.getElementById('replyModal{{ $complaint->id }}').close()" class="btn">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Send Reply</button>
                                                    </div>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop"><button>close</button></form>
                                        </dialog>
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
                            </div>
                        @endif
                    </div>
                </div>
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
                            <span class="sidebar-text">Backup</span>
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
                                <span class="sidebar-text">Recharge History</span>
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
                        <details open>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                <span class="sidebar-text">Offer Settings</span>
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
                                <span class="sidebar-text">Reseller</span>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="sidebar-text">Admin Account</span>
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.profile') }}">My Profile</a></li>
                                <li><a href="/admin/manage-admins">Manage Admin Users</a></li>
                                <li><a href="{{ route('admin.change.credentials') }}">Change Password & PIN</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <a class="active bg-primary text-primary-content" href="{{ route('admin.complaints') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="sidebar-text">Complain</span>
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
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="sidebar-text">Logout</span>
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
</body>
</html>

