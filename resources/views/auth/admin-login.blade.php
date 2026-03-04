<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Admin Login' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
    <div class="min-h-screen flex items-center justify-center">
        <div class="card bg-base-100 shadow-2xl w-full max-w-md mx-4">
            <div class="card-body">
                <div class="text-center mb-6">
                    <a href="/" class="flex items-center justify-center gap-2 mb-4">
                        @if(optional($settings)->company_logo_url)
                            <img src="{{ asset($settings->company_logo_url) }}" alt="Logo" class="w-12 h-12 rounded-lg object-contain bg-base-100">
                        @else
                            <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7l8-4 8 4v6a8 8 0 11-16 0V7z" />
                                </svg>
                            </div>
                        @endif
                    </a>
                    <h2 class="text-2xl font-bold">Admin Login</h2>
                    <p class="text-base-content/60 mt-1">Enter admin credentials to access dashboard</p>
                </div>

                <form action="{{ route('admin.login') }}" method="POST">
                    @csrf
                    
                    @if(session('error'))
                        <div class="alert alert-error mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Admin Email</span>
                        </label>
                        <input type="email" name="email" placeholder="admin@codecartel.com" class="input input-bordered w-full @error('email') input-error @endif" value="{{ old('email') }}" required />
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Password</span>
                        </label>
                        <input type="password" name="password" placeholder="••••••••" class="input input-bordered w-full" required />
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Admin PIN</span>
                        </label>
                        <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="4"
                               placeholder="1234" class="input input-bordered w-full" required />
                    </div>

                    @error('email')
                        <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror
                    @error('pin')
                        <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror

                    <div class="form-control mt-6">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm" />
                            <span class="label-text">Remember me</span>
                        </label>
                    </div>

                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary w-full">Sign In as Admin</button>
                    </div>

                    <div class="form-control mt-3 text-center">
                        <a href="/admin/forgot-password" class="link link-hover text-sm font-medium">Forgot password?</a>
                    </div>
                </form>

                <div class="divider">OR</div>

                <div class="text-center">
                    <a href="/login" class="link link-primary text-sm font-medium block mb-2">
                        User Login
                    </a>
                    <a href="/" class="btn btn-outline btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

