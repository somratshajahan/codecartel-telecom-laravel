<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Google OTP' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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

            <main class="flex-1 p-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold">Google OTP</h1>
                        <p class="text-sm text-base-content/70">Sidebar-এর Google OTP option থেকে Google Authenticator setup/manage করুন।</p>
                    </div>
                    <a href="{{ route('user.profile') }}" class="btn btn-outline">Back to My Profile</a>
                </div>

                @if(session('success'))
                <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
                @endif

                @if($errors->any())
                <div class="alert alert-error mb-4">
                    <ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
                @endif

                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="card-title text-2xl">Google Authenticator</h2>
                                <p class="text-sm text-base-content/70">এই page থেকে setup করলে login-এর সময় password + PIN এর সাথে 6 digit OTP লাগবে।</p>
                            </div>
                            <div class="flex gap-2 items-center">
                                <span class="badge {{ optional($settings)->google_otp_enabled ? 'badge-success' : 'badge-ghost' }}">{{ optional($settings)->google_otp_enabled ? 'Admin Enabled' : 'Admin Disabled' }}</span>
                                <span class="badge {{ $user->google_otp_enabled ? 'badge-primary' : 'badge-warning' }}">{{ $user->google_otp_enabled ? 'Setup Complete' : 'Not Setup' }}</span>
                            </div>
                        </div>

                        @if(optional($settings)->google_otp_enabled)
                        <div class="grid lg:grid-cols-2 gap-6 mt-4">
                            <div class="bg-base-200 rounded-xl p-5 space-y-4">
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between gap-4"><span class="font-semibold">Issuer</span><span>{{ $settings->google_otp_issuer ?: $settings->company_name ?: 'Codecartel Telecom' }}</span></div>
                                    <div class="flex justify-between gap-4"><span class="font-semibold">Account</span><span>{{ $user->email }}</span></div>
                                    <div class="flex justify-between gap-4"><span class="font-semibold">Verified</span><span>{{ $user->google_otp_confirmed_at ? $user->google_otp_confirmed_at->format('d M Y h:i A') : 'Pending setup' }}</span></div>
                                </div>

                                @if($user->google_otp_enabled)
                                <div><label class="label"><span class="label-text font-medium">Saved Secret</span></label><input type="text" class="input input-bordered w-full" value="{{ $googleOtpMaskedSecret }}" readonly /></div>
                                <div class="alert alert-info text-sm">Google OTP active আছে। Off করতে চাইলে নিচে current PIN দিন।</div>
                                @else
                                <div><label class="label"><span class="label-text font-medium">Manual setup key</span></label><input type="text" class="input input-bordered w-full font-mono" value="{{ $googleOtpSetupSecret }}" readonly /></div>
                                <div><label class="label"><span class="label-text font-medium">Authenticator URI</span></label><textarea class="textarea textarea-bordered w-full text-xs" rows="4" readonly>{{ $googleOtpOtpAuthUrl }}</textarea></div>
                                <div class="alert alert-info text-sm">Google Authenticator app-এ manual key add করুন, তারপর generated 6 digit OTP নিচে দিয়ে enable করুন।</div>
                                @endif
                            </div>

                            <div class="space-y-6">
                                @if(! $user->google_otp_enabled)
                                <div class="bg-base-200 rounded-xl p-5">
                                    <h3 class="font-semibold text-lg mb-3">Enable Google OTP</h3>
                                    <form action="{{ route('user.profile.google-otp.enable') }}" method="POST" class="space-y-4">
                                        @csrf
                                        <div class="form-control">
                                            <label class="label"><span class="label-text">6 Digit OTP</span></label>
                                            <input type="text" name="otp" value="{{ old('otp') }}" class="input input-bordered" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter app OTP" required />
                                            @error('otp')<span class="text-error text-sm mt-2">{{ $message }}</span>@enderror
                                        </div>
                                        <button type="submit" class="btn btn-primary w-full">Enable Google Authenticator</button>
                                    </form>
                                </div>
                                @endif

                                <div class="bg-base-200 rounded-xl p-5">
                                    <h3 class="font-semibold text-lg mb-3">Disable Google OTP</h3>
                                    <form action="{{ route('user.profile.google-otp.disable') }}" method="POST" class="space-y-4">
                                        @csrf
                                        <div class="form-control">
                                            <label class="label"><span class="label-text">Current PIN</span></label>
                                            <input type="password" name="disable_pin" class="input input-bordered" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" placeholder="Enter current PIN" {{ $user->google_otp_enabled ? 'required' : '' }} />
                                            @error('disable_pin')<span class="text-error text-sm mt-2">{{ $message }}</span>@enderror
                                        </div>
                                        <button type="submit" class="btn btn-outline btn-error w-full" {{ $user->google_otp_enabled ? '' : 'disabled' }}>Disable Google Authenticator</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning mt-4 text-sm">Google OTP এখন admin panel থেকে off আছে। তাই login আগের মতো password + PIN দিয়ে চলবে.</div>
                        @endif
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
                            <li><a class="active" href="{{ route('user.profile.google-otp') }}">Google OTP</a></li>
                            <li><a href="{{ route('user.profile.api') }}">API</a></li>
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