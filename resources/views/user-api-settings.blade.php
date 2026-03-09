<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'API Settings' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                            <li><a href="{{ route('user.profile.google-otp') }}">Google OTP</a></li>
                            <li><a href="{{ route('user.profile.api') }}">API</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full text-left">Logout</button></form>
                            </li>
                        </ul>
                    </div>

                    <span class="font-semibold hidden sm:inline">{{ $user->name }}</span>
                </div>
            </div>

            <main class="flex-1 p-6 space-y-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">API Settings</h1>
                        <p class="text-sm text-base-content/70">Your API key দেখুন, reset করুন, service on/off করুন, এবং API domain add করুন।</p>
                    </div>
                    <a href="{{ route('user.profile') }}" class="btn btn-outline">Back to My Profile</a>
                </div>

                @if(session('success'))
                <div class="alert alert-success"><span>{{ session('success') }}</span></div>
                @endif

                @if($errors->any())
                <div class="alert alert-error">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="flex-1">
                            <h2 class="card-title text-2xl">Your API Key</h2>
                            <p class="text-sm text-base-content/70">এই key টা API integration-এর জন্য use হবে।</p>
                            <input type="text" class="input input-bordered w-full mt-3 font-mono" value="{{ $user->api_key }}" readonly />
                        </div>
                        <form method="POST" action="{{ route('user.profile.api.reset') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Reset API Key</button>
                        </form>
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-6">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title text-2xl">New Web For Api</h2>
                            <form method="POST" action="{{ route('user.profile.api.domains.store') }}" class="space-y-4">
                                @csrf
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Domain</span></label>
                                    <input type="text" name="domain" value="{{ old('domain') }}" class="input input-bordered" placeholder="eg: example.com" required />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Provider</span></label>
                                    <select name="provider" class="select select-bordered">
                                        <option value="Etross" {{ old('provider', 'Etross') === 'Etross' ? 'selected' : '' }}>Etross</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-full">Submit</button>
                            </form>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title text-2xl">Domain List</h2>
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Domain</th>
                                            <th>Provider</th>
                                            <th>Date</th>
                                            <th class="text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($domains as $domain)
                                        <tr>
                                            <td>{{ $domain->domain }}</td>
                                            <td>{{ $domain->provider }}</td>
                                            <td>{{ optional($domain->created_at)->format('d M Y h:i A') }}</td>
                                            <td class="text-right">
                                                <form method="POST" action="{{ route('user.profile.api.domains.destroy', $domain->id) }}" onsubmit="return confirm('Are you sure you want to delete this domain?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline btn-error">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-base-content/60">No domain added yet.</td>
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
                    <p>Copyright © 2026 - All right reserved by {{ optional($settings)->company_name ?? 'Codecartel Telecom' }} | Version 1.0.0</p>
                </div>
            </footer>
        </div>

        <div class="drawer-side">
            <label for="my-drawer" class="drawer-overlay"></label>
            <ul class="menu p-4 w-60 min-h-full bg-base-100 text-base-content">
                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li>
                    <details open>
                        <summary>My Accounts</summary>
                        <ul class="p-2">
                            <li><a href="{{ route('user.profile') }}">My Profile</a></li>
                            <li><a href="{{ route('user.profile.google-otp') }}">Google OTP</a></li>
                            <li><a class="active" href="{{ route('user.profile.api') }}">API</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full text-left">Logout</button></form>
                </li>
            </ul>
        </div>
    </div>
</body>

</html>