<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Profile' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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

<main class="flex-1 p-6">
                <h1 class="text-3xl font-bold mb-6">My Profile</h1>

                @if(session('success'))
                    <div class="alert alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Row 1: Info Cards -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title mb-4">Personal Information</h2>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="font-semibold">Name:</span>
                                    <span>{{ $user->name }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Email:</span>
                                    <span>{{ $user->email }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Mobile:</span>
                                    <span>{{ $user->mobile ?? 'N/A' }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">NID:</span>
                                    <span>{{ $user->nid ?? 'N/A' }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Level:</span>
                                    <span class="badge badge-primary">{{ ucfirst($user->level ?? 'N/A') }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Status:</span>
                                    <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-error' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title mb-4">Balance Information</h2>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="font-semibold">Main Balance:</span>
                                    <span class="text-primary font-bold">৳ {{ number_format($user->main_bal ?? 0, 2) }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Drive Balance:</span>
                                    <span class="text-secondary font-bold">৳ {{ number_format($user->drive_bal ?? 0, 2) }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Bank Balance:</span>
                                    <span class="text-accent font-bold">৳ {{ number_format($user->bank_bal ?? 0, 2) }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Stock:</span>
                                    <span class="font-bold">৳ {{ number_format($user->stock ?? 0, 2) }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Total Balance:</span>
                                    <span class="text-success font-bold text-lg">৳ {{ number_format(($user->main_bal ?? 0) + ($user->drive_bal ?? 0) + ($user->bank_bal ?? 0), 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title mb-4">Account Details</h2>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="font-semibold">User ID:</span>
                                    <span>{{ $user->id }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Username:</span>
                                    <span>{{ $user->username ?? 'N/A' }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Joined:</span>
                                    <span>{{ $user->created_at->format('d M Y') }}</span>
                                </div>
                                <div class="divider my-1"></div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Last Updated:</span>
                                    <span>{{ $user->updated_at->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Profile Picture (Full Width) -->
                <div class="mt-6">
                    <div class="card bg-base-100 shadow-xl max-w-md mx-auto">
                        <div class="card-body">
                            <h2 class="card-title mb-4 text-center">Profile Picture</h2>
                            <div class="flex flex-col items-center">
                                <div class="avatar mb-4">
                                    <div class="w-32 rounded-full bg-primary text-primary-content flex items-center justify-center">
                                        @if($user->profile_picture)
                                            <img src="{{ asset($user->profile_picture) }}" alt="Profile" class="w-full h-full object-cover rounded-full">
                                        @else
                                            <span class="font-bold text-4xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <form action="{{ route('user.profile.picture') }}" method="POST" enctype="multipart/form-data" class="w-full">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-control mb-4">
                                        <input type="file" name="profile_picture" class="file-input file-input-bordered file-input-primary w-full" accept="image/*" />
                                    </div>
                                    <button type="submit" class="btn btn-primary w-full">Upload Picture</button>
                                </form>
                                @if($user->profile_picture)
                                    <form action="{{ route('user.profile.picture.delete') }}" method="POST" class="w-full mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-error btn-outline w-full">Remove Picture</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Profile Edit Forms (Last Row) -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                    <!-- Edit Profile -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title mb-4">Edit Profile</h2>
                            <form action="{{ route('user.profile.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-control mb-3">
                                    <label class="label"><span class="label-text">Name</span></label>
                                    <input type="text" name="name" value="{{ $user->name }}" class="input input-bordered" required />
                                </div>
                                <div class="form-control mb-3">
                                    <label class="label"><span class="label-text">Mobile</span></label>
                                    <input type="text" name="mobile" value="{{ $user->mobile ?? '' }}" placeholder="01XXXXXXXXX" class="input input-bordered" />
                                </div>
                                <div class="form-control mb-4">
                                    <label class="label"><span class="label-text">NID</span></label>
                                    <input type="text" name="nid" value="{{ $user->nid ?? '' }}" class="input input-bordered" />
                                </div>
                                <button type="submit" class="btn btn-primary w-full">Update Profile</button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title mb-4">Change Password</h2>
                            <form action="{{ route('user.profile.password') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-control mb-3">
                                    <label class="label"><span class="label-text">Current Password</span></label>
                                    <input type="password" name="current_password" class="input input-bordered" required />
                                </div>
                                <div class="form-control mb-3">
                                    <label class="label"><span class="label-text">New Password</span></label>
                                    <input type="password" name="new_password" class="input input-bordered" required minlength="6" />
                                </div>
                                <div class="form-control mb-4">
                                    <label class="label"><span class="label-text">Confirm New Password</span></label>
                                    <input type="password" name="new_password_confirmation" class="input input-bordered" required />
                                </div>
                                <button type="submit" class="btn btn-primary w-full">Change Password</button>
                            </form>
                        </div>
                    </div>

                    <!-- Change PIN -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title mb-4">Change PIN</h2>
                            <form action="{{ route('user.profile.pin') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-control mb-3">
                                    <label class="label"><span class="label-text">Current PIN</span></label>
                                    <input type="password" name="current_pin" class="input input-bordered" required maxlength="4" pattern="[0-9]{4}" />
                                </div>
                                <div class="form-control mb-3">
                                    <label class="label"><span class="label-text">New PIN</span></label>
                                    <input type="password" name="new_pin" class="input input-bordered" required maxlength="4" pattern="[0-9]{4}" />
                                </div>
                                <div class="form-control mb-4">
                                    <label class="label"><span class="label-text">Confirm New PIN</span></label>
                                    <input type="password" name="new_pin_confirmation" class="input input-bordered" required maxlength="4" pattern="[0-9]{4}" />
                                </div>
                                <button type="submit" class="btn btn-primary w-full">Change PIN</button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="footer items-center p-4 bg-base-300 text-base-content">
                <div class="items-center grid-flow-col">
                    <p>Copyright © 2026 - All right reserved by {{ optional($settings)->company_name ?? 'Codecartel Telecom' }} | Version 1.0.0</p>
                </div>
            </footer>
        </div>

        <div class="drawer-side">
            <label for="my-drawer" class="drawer-overlay"></label>
            <ul class="menu p-4 w-60 min-h-full bg-base-100 text-base-content">
                <li><a href="{{ route('dashboard') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>Dashboard</a></li>
                <li><details><summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>New Request</span></summary><ul class="p-2"><li><a href="#">Flexiload</a></li><li><a href="#">Internet Pack</a></li><li><a href="{{ route('user.drive') }}">Drive</a></li><li><a href="#">Bkash</a></li><li><a href="#">Nagad</a></li><li><a href="#">Rocket</a></li><li><a href="#">Upay</a></li><li><a href="#">Islami Bank</a></li><li><a href="#">Bulk Flexi</a></li></ul></details></li>
                <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Pending Request</a></li>
                <li><details><summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5M21 21v-5h-5M4 4l16 16" /></svg>History</span></summary><ul class="p-2"><li><a href="{{ route('user.all.history') }}">All history</a></li><li><a href="#">Flexiload</a></li><li><a href="#">Internet Pack</a></li><li><a href="#">Drive</a></li><li><a href="#">Bkash</a></li><li><a href="#">Nagad</a></li><li><a href="#">Rocket</a></li><li><a href="#">Upay</a></li><li><a href="#">Islami Bank</a></li></ul></details></li>
                <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex items-center gap-2 w-full"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>Logout</button></form></li>
            </ul>
        </div>
    </div>
</body>
</html>
