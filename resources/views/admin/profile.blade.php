<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'My Profile' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </label>
                </div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - My Profile</a></div>
            </div>

            <div class="p-6">
                <div class="max-w-6xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Side - Profile Info -->
                        <div class="lg:col-span-2">
                            <div class="card bg-base-100 shadow-xl">
                                <div class="card-body">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="avatar placeholder">
                                            <div class="bg-primary text-primary-content rounded-full w-20">
                                                @if($admin->profile_picture)
                                                    <img src="{{ asset('storage/' . $admin->profile_picture) }}" alt="Profile" class="rounded-full">
                                                @else
                                                    <span class="text-3xl">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <h2 class="text-3xl font-bold">{{ $admin->name }}</h2>
                                            <p class="text-base-content/60">Administrator</p>
                                        </div>
                                    </div>

                                    <div class="divider"></div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text font-semibold">Full Name</span>
                                            </label>
                                            <div class="input input-bordered flex items-center">
                                                {{ $admin->name }}
                                            </div>
                                        </div>

                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text font-semibold">Email Address</span>
                                            </label>
                                            <div class="input input-bordered flex items-center">
                                                {{ $admin->email }}
                                            </div>
                                        </div>

                                        @if($admin->username)
                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text font-semibold">Username</span>
                                            </label>
                                            <div class="input input-bordered flex items-center">
                                                {{ $admin->username }}
                                            </div>
                                        </div>
                                        @endif

                                        @if($admin->mobile)
                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text font-semibold">Mobile Number</span>
                                            </label>
                                            <div class="input input-bordered flex items-center">
                                                {{ $admin->mobile }}
                                            </div>
                                        </div>
                                        @endif

                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text font-semibold">Account Status</span>
                                            </label>
                                            <div class="input input-bordered flex items-center">
                                                <span class="badge {{ $admin->is_active ? 'badge-success' : 'badge-error' }}">
                                                    {{ $admin->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text font-semibold">Account Type</span>
                                            </label>
                                            <div class="input input-bordered flex items-center">
                                                <span class="badge badge-primary">Administrator</span>
                                            </div>
                                        </div>

                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text font-semibold">Member Since</span>
                                            </label>
                                            <div class="input input-bordered flex items-center">
                                                {{ $admin->created_at->format('d M Y') }}
                                            </div>
                                        </div>

                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text font-semibold">Last Updated</span>
                                            </label>
                                            <div class="input input-bordered flex items-center">
                                                {{ $admin->updated_at->format('d M Y, h:i A') }}
                                            </div>
                                        </div>
                                    </div>

                                    @if($admin->nid)
                                    <div class="divider"></div>
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">NID Number</span>
                                        </label>
                                        <div class="input input-bordered flex items-center">
                                            {{ $admin->nid }}
                                        </div>
                                    </div>
                                    @endif

                                    <div class="divider"></div>

                                    <div class="flex gap-4 justify-center">
                                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                            </svg>
                                            Back to Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side - Profile Picture Upload -->
                        <div class="lg:col-span-1">
                            <div class="card bg-base-100 shadow-xl">
                                <div class="card-body">
                                    <h3 class="text-xl font-bold mb-4">Profile Picture</h3>
                                    
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="avatar placeholder">
                                            <div class="bg-primary text-primary-content rounded-full w-32 h-32">
                                                @if($admin->profile_picture)
                                                    <img id="preview-image" src="{{ asset('storage/' . $admin->profile_picture) }}" alt="Profile" class="rounded-full w-full h-full object-cover">
                                                @else
                                                    <span id="preview-initial" class="text-5xl">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                                                    <img id="preview-image" src="" alt="Preview" class="rounded-full w-full h-full object-cover hidden">
                                                @endif
                                            </div>
                                        </div>

                                        <form method="POST" action="{{ route('admin.profile.picture.update') }}" enctype="multipart/form-data" class="w-full">
                                            @csrf
                                            @method('PUT')
                                            
                                            <div class="form-control w-full">
                                                <label class="label">
                                                    <span class="label-text font-semibold">Upload New Picture</span>
                                                </label>
                                                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="file-input file-input-bordered w-full" required>
                                                <label class="label">
                                                    <span class="label-text-alt">Max 2MB (JPG, PNG, GIF)</span>
                                                </label>
                                                @error('profile_picture')
                                                    <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                                                @enderror
                                            </div>

                                            <button type="submit" class="btn btn-primary w-full mt-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                                Upload Picture
                                            </button>
                                        </form>

                                        @if($admin->profile_picture)
                                        <form method="POST" action="{{ route('admin.profile.picture.delete') }}" class="w-full">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-error btn-outline w-full" onclick="return confirm('Are you sure you want to remove your profile picture?')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Remove Picture
                                            </button>
                                        </form>
                                        @endif
                                    </div>

                                    @if(session('picture_success'))
                                        <div class="alert alert-success mt-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span>{{ session('picture_success') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.getElementById('profile_picture').addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewImage = document.getElementById('preview-image');
                            const previewInitial = document.getElementById('preview-initial');
                            previewImage.src = e.target.result;
                            previewImage.classList.remove('hidden');
                            if (previewInitial) previewInitial.classList.add('hidden');
                        }
                        reader.readAsDataURL(file);
                    }
                });

                // Close all submenu details on page load
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('#sidebar details').forEach(detail => {
                        detail.removeAttribute('open');
                    });
                });
            </script>
        </div>
        
        <!-- Sidebar -->
        <div class="drawer-side z-40">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 min-h-screen border-r border-base-200">
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
                    <li><a href="{{ route('admin.dashboard') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>Dashboard</a></li>
                    <li><a href="{{ route('admin.backup') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>Backup</a></li>
                    <li><details><summary><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>Reseller</summary><ul><li><a href="{{ route('admin.resellers') }}">All Reseller</a></li></ul></details></li>
                    <li><details><summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>Admin Account</summary><ul><li><a href="{{ route('admin.profile') }}" class="active">My Profile</a></li><li><a href="{{ route('admin.manage.admins') }}">Manage Admin Users</a></li><li><a href="{{ route('admin.change.credentials') }}">Change Password & PIN</a></li></ul></details></li>
                    <li><details><summary><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>Settings</summary><ul><li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li><li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li><li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li></ul></details></li>
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
    </div>
</body>
</html>
