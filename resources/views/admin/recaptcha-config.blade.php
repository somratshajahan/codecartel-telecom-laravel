<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>reCAPTCHA Credentials - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-none"><label for="my-drawer" class="btn btn-square btn-ghost"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg></label></div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - reCAPTCHA</a></div>
            </div>
            <div class="p-6">
                <div class="max-w-5xl mx-auto">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body space-y-6">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-3xl font-bold">🛡️ Google reCAPTCHA Credentials</h2>
                                    <p class="text-sm opacity-70">User login, registration আর admin login page-এর জন্য reCAPTCHA credentials এখানে manage করুন।</p>
                                </div>
                                @if(session('success'))
                                    <div class="badge badge-success badge-lg">{{ session('success') }}</div>
                                @endif
                            </div>
                            @if($errors->any())
                                <div class="alert alert-error"><ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            <form method="POST" action="{{ route('admin.recaptcha.update') }}" class="space-y-6">
                                @csrf
                                <div class="bg-base-200 p-6 rounded-lg space-y-4">
                                    <h3 class="text-xl font-semibold">Google reCAPTCHA Setup</h3>
                                    <p class="text-sm text-base-content/70">Enable/disable button `Security Modual` page-এ থাকবে। এখানে শুধু credentials save করবেন।</p>
                                    <div class="form-control"><label class="label"><span class="label-text font-medium">reCAPTCHA Site Key</span></label><input type="text" name="recaptcha_site_key" class="input input-bordered w-full @error('recaptcha_site_key') input-error @enderror" value="{{ old('recaptcha_site_key', $settings->recaptcha_site_key) }}" placeholder="6Le... site key">@error('recaptcha_site_key')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror</div>
                                    <div class="form-control"><label class="label"><span class="label-text font-medium">reCAPTCHA Secret Key</span></label><textarea name="recaptcha_secret_key" rows="4" class="textarea textarea-bordered w-full @error('recaptcha_secret_key') textarea-error @enderror" placeholder="6Le... secret key">{{ old('recaptcha_secret_key', $settings->recaptcha_secret_key) }}</textarea>@error('recaptcha_secret_key')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror</div>
                                </div>
                                <div class="bg-base-200 p-6 rounded-lg text-sm text-base-content/80">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li>Security Modual থেকে `Google reCAPTCHA` on/off control হবে।</li>
                                        <li>Credentials filled + feature enabled থাকলে login/register/admin login page-এ widget render হবে।</li>
                                    </ul>
                                </div>
                                <div class="flex justify-center"><button type="submit" class="btn btn-primary btn-lg">Save reCAPTCHA Credentials</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="drawer-side z-40">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 min-h-screen border-r border-base-200">
                <div class="p-4 border-b border-base-200"><a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2"><span class="text-lg font-bold sidebar-text">{{ optional($settings)->company_name ?? 'Codecartel' }}</span></a></div>
                <ul class="menu p-4 gap-1">
                    <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li><details open><summary><span class="sidebar-text">Settings</span></summary><ul><li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li><li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li><li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li><li><a href="{{ route('admin.firebase.config') }}">Firebase Credentials</a></li><li><a href="{{ route('admin.google.otp.config') }}">Google OTP</a></li><li><a href="{{ route('admin.recaptcha.config') }}" class="active">reCAPTCHA Credentials</a></li><li><a href="{{ route('admin.tawk.config') }}">Tawk Chat Credentials</a></li></ul></details></li>
                    <li><form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">Logout</button></form></li>
                </ul>
            </aside>
        </div>
    </div>
</body>
</html>