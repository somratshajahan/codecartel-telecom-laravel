<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Login Notice' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
        <input id="notice-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30 border-b border-base-200">
                <div class="flex-none lg:hidden">
                    <label for="notice-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1 px-2">
                    <a href="{{ route('admin.notice.index') }}" class="text-lg md:text-xl font-bold hover:text-primary transition-colors">
                        {{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Login Notice
                    </a>
                </div>
                <div class="flex-none gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm hidden md:inline-flex">Dashboard</a>
                 
                </div>
            </div>

            <div class="p-4 md:p-6">
                <div class="max-w-4xl mx-auto space-y-6">
                    <div class="bg-base-100 shadow-xl rounded-2xl border border-base-200">
                        <div class="p-6 md:p-8 border-b border-base-200">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h1 class="text-2xl md:text-3xl font-bold">Login Notice</h1>
                                  
                                </div>
                               
                            </div>
                        </div>

                        <div class="p-6 md:p-8">
                            @if(session('success'))
                            <div class="alert alert-success mb-6">
                                <span>{{ session('success') }}</span>
                            </div>
                            @endif

                            @if($errors->any())
                            <div class="alert alert-error mb-6">
                                <span>{{ $errors->first() }}</span>
                            </div>
                            @endif

                            <form action="{{ route('admin.notice.update') }}" method="POST" class="space-y-5">
                                @csrf

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-semibold">Notice Text</span>
                                    </label>
                                    <textarea
                                        name="notice_text"
                                        rows="7"
                                        class="textarea textarea-bordered w-full"
                                        placeholder="Enter login notice...">{{ old('notice_text', $notice->notice_text ?? '') }}</textarea>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 justify-end">
                                
                                    <button type="submit" class="btn btn-primary">Update Notice</button>
                                        <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Back to Dashboard</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="drawer-side z-40">
            <label for="notice-drawer" class="drawer-overlay"></label>
            <aside class="bg-base-100 w-72 min-h-screen border-r border-base-200">
                <div class="p-4 border-b border-base-200">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                        @if(optional($settings)->company_logo_url)
                        <img src="{{ asset(optional($settings)->company_logo_url) }}" alt="Logo" class="h-10 w-10 object-contain rounded-lg">
                        @else
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        @endif
                        <div>
                            <div class="font-bold leading-tight">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</div>
                            <div class="text-xs text-base-content/60">Admin Panel</div>
                        </div>
                    </a>
                </div>

                <ul class="menu p-4 gap-2 text-sm">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active bg-primary text-primary-content' : '' }}">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.homepage.edit') }}" class="{{ request()->routeIs('admin.homepage.edit') ? 'active bg-primary text-primary-content' : '' }}">
                            General Settings
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.branding') }}" class="{{ request()->routeIs('admin.branding') ? 'active bg-primary text-primary-content' : '' }}">
                            Branding
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.device.logs') }}" class="{{ request()->routeIs('admin.device.logs') ? 'active bg-primary text-primary-content' : '' }}">
                            Device Logs
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.notice.index') }}" class="active bg-primary text-primary-content">
                            Login Notice
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.mail.config') }}" class="{{ request()->routeIs('admin.mail.config') ? 'active bg-primary text-primary-content' : '' }}">
                            Mail Config
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.sms.config') }}" class="{{ request()->routeIs('admin.sms.config') ? 'active bg-primary text-primary-content' : '' }}">
                            SMS Config
                        </a>
                    </li>
                    @auth
                    <li class="pt-2 mt-2 border-t border-base-200">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost justify-start w-full">Logout</button>
                        </form>
                    </li>
                    @endauth
                </ul>
            </aside>
        </div>
    </div>
</body>

</html>