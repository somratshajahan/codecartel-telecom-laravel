<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Edit Profile' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Edit Profile</a></div>
            </div>

            <div class="p-6">
                <div class="max-w-4xl mx-auto">
                    @if(session('success'))
                        <div class="alert alert-success mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="text-3xl font-bold mb-6">Edit Profile</h2>

                            <form method="POST" action="{{ route('admin.profile.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">Full Name <span class="text-error">*</span></span>
                                        </label>
                                        <input type="text" name="name" value="{{ old('name', $admin->name) }}" class="input input-bordered @error('name') input-error @enderror" required>
                                        @error('name')
                                            <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">Email Address <span class="text-error">*</span></span>
                                        </label>
                                        <input type="email" name="email" value="{{ old('email', $admin->email) }}" class="input input-bordered @error('email') input-error @enderror" required>
                                        @error('email')
                                            <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">Username</span>
                                        </label>
                                        <input type="text" name="username" value="{{ old('username', $admin->username) }}" class="input input-bordered @error('username') input-error @enderror">
                                        @error('username')
                                            <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">Mobile Number</span>
                                        </label>
                                        <input type="text" name="mobile" value="{{ old('mobile', $admin->mobile) }}" class="input input-bordered @error('mobile') input-error @enderror" placeholder="01XXXXXXXXX">
                                        @error('mobile')
                                            <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                                        @enderror
                                    </div>

                                    <div class="form-control md:col-span-2">
                                        <label class="label">
                                            <span class="label-text font-semibold">NID Number</span>
                                        </label>
                                        <input type="text" name="nid" value="{{ old('nid', $admin->nid) }}" class="input input-bordered @error('nid') input-error @enderror">
                                        @error('nid')
                                            <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                                        @enderror
                                    </div>
                                </div>

                                <div class="divider"></div>

                                <div class="flex gap-4 justify-center">
                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Update Profile
                                    </button>
                                    <a href="{{ route('admin.profile') }}" class="btn btn-outline">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        @include('admin')
    </div>
</body>
</html>
