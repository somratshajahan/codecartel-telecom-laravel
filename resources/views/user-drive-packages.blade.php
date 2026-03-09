<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Drive Packages' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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

<body class="min-h-screen bg-base-200 flex flex-col">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a href="{{ route('dashboard') }}" class="px-2 text-xl font-bold">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</a>
                </div>
                <div class="flex-none gap-2">
                    <a href="{{ route('user.drive') }}" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </a>
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
                                <span class="font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </div>
                        </div>
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            @if(Auth::user() && Auth::user()->hasPermission('profile'))
                            <li><a href="{{ route('user.profile') }}">Profile</a></li>
                            <li><a href="{{ route('user.profile') }}">Settings</a></li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <span class="font-semibold hidden sm:inline">{{ Auth::user()->name }}</span>
                </div>
            </div>

            <div class="container mx-auto p-6 flex-1">
                <h1 class="text-3xl font-bold mb-6">{{ $operator }} Drive Packages</h1>

                @if($packages->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($packages as $package)
                    <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow">
                        <div class="card-body">
                            <h3 class="card-title text-lg">{{ $package->name }}</h3>
                            <div class="divider my-2"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Price:</span>
                                <span class="font-bold text-lg">৳{{ $package->price }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Commission:</span>
                                <span class="font-bold text-success">৳{{ $package->commission }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Selling Price:</span>
                                <span class="font-bold text-primary">৳{{ $package->price - $package->commission }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span>Expires:</span>
                                <span>{{ $package->expire->format('d M Y') }}</span>
                            </div>
                            <div class="card-actions justify-end mt-4">
                                <a href="{{ route('user.drive.buy', ['operator' => $operator, 'package' => $package->id]) }}" class="btn btn-primary btn-sm">Buy</a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="alert alert-info">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>No active packages available for {{ $operator }} at the moment.</span>
                </div>
                @endif
            </div>

            <footer class="footer items-center p-4 bg-base-300 text-base-content justify-center">
                <div class="items-center grid-flow-col">
                    <p>Copyright © 2026 - All right reserved by {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</p>
                </div>
            </footer>
        </div>

        <div class="drawer-side">
            <label for="my-drawer" class="drawer-overlay"></label>
            <ul class="menu p-4 w-60 min-h-full bg-base-100 text-base-content">
                <li><a href="{{ route('dashboard') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>Dashboard</a></li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>New Request</span></summary>
                        <ul class="p-2">
                            <li><a href="{{ route('user.flexi') }}">Flexiload</a></li>
                            <li><a href="#">Internet Pack</a></li>
                            <li><a href="{{ route('user.drive') }}">Drive</a></li>
                            <li><a href="#">Bkash</a></li>
                            <li><a href="#">Nagad</a></li>
                            <li><a href="#">Rocket</a></li>
                            <li><a href="#">Upay</a></li>
                            <li><a href="#">Islami Bank</a></li>
                            <li><a href="{{ route('user.flexi') }}">Bulk Flexi</a></li>
                        </ul>
                    </details>
                </li>
                <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>Pending Request</a></li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5M21 21v-5h-5M4 4l16 16" />
                                </svg>History</span></summary>
                        <ul class="p-2">
                            <li><a href="#">All history</a></li>
                            <li><a href="{{ route('user.flexi') }}">Flexiload</a></li>
                            <li><a href="#">Internet Pack</a></li>
                            <li><a href="#">Drive</a></li>
                            <li><a href="#">Bkash</a></li>
                            <li><a href="#">Nagad</a></li>
                            <li><a href="#">Rocket</a></li>
                            <li><a href="#">Upay</a></li>
                            <li><a href="#">Islami Bank</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex items-center gap-2 w-full"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>Logout</button></form>
                </li>
            </ul>
        </div>
    </div>
</body>

</html>