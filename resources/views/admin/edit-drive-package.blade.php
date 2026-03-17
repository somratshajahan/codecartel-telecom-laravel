<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Edit Package' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Edit Package</a></div>
            </div>

            <div class="p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="flex items-center gap-4 mb-6">
                        <a href="{{ route('admin.manage.drive.package', ['operator' => $package->operator]) }}" class="btn btn-ghost btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            Back
                        </a>
                        <h1 class="text-3xl font-bold">Edit Package</h1>
                    </div>

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.manage.drive.package.update.save', ['operator' => $package->operator, 'id' => $package->id]) }}">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Package Name</span></label>
                                        <input type="text" name="package_name" value="{{ $package->name }}" class="input input-bordered" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Price</span></label>
                                        <input type="number" name="price" value="{{ $package->price }}" step="0.01" class="input input-bordered" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Commission</span></label>
                                        <input type="number" name="commission" value="{{ $package->commission }}" step="0.01" class="input input-bordered" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Expire</span></label>
                                        <input type="date" name="expire" value="{{ $package->expire->format('Y-m-d') }}" class="input input-bordered" required />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Countdown (Minutes)</span></label>
                                        <input type="number" name="countdown_minutes" value="{{ old('countdown_minutes', $package->countdown_minutes) }}" min="1" max="43200" class="input input-bordered" placeholder="e.g. 60" />
                                        <label class="label"><span class="label-text-alt text-base-content/60">Optional. দিলে active থাকা অবস্থায় auto expire হবে।</span></label>
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Status</span></label>
                                        <select name="status" class="select select-bordered" required>
                                            <option value="active" {{ $package->status == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="deactive" {{ $package->status == 'deactive' ? 'selected' : '' }}>Deactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6 flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Save Changes
                                    </button>
                                    <a href="{{ route('admin.manage.drive.package', ['operator' => $package->operator]) }}" class="btn btn-ghost">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
                    <li><details><summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>Admin Account</summary><ul><li><a href="{{ route('admin.profile') }}">My Profile</a></li><li><a href="{{ route('admin.manage.admins') }}">Manage Admin Users</a></li><li><a href="{{ route('admin.change.credentials') }}">Change Password & PIN</a></li></ul></details></li>
                    <li><details open><summary><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>Settings</summary><ul><li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li><li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li><li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li></ul></details></li>
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
