<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tawk Chat Credentials - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Tawk Chat</a></div>
            </div>
            <div class="p-6">
                <div class="max-w-5xl mx-auto">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body space-y-6">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-3xl font-bold">💬 Tawk Chat Credentials</h2>
                                   
                                </div>
                                @if(session('success'))
                                    <div class="badge badge-success badge-lg">{{ session('success') }}</div>
                                @endif
                            </div>
                            @if($errors->any())
                                <div class="alert alert-error"><ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            <form method="POST" action="{{ route('admin.tawk.update') }}" class="space-y-6">
                                @csrf
                                <div class="bg-base-200 p-6 rounded-lg space-y-4">
                                    <h3 class="text-xl font-semibold">Tawk Widget Setup</h3>
                                  
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-control"><label class="label"><span class="label-text font-medium">Tawk Property ID</span></label><input type="text" name="tawk_property_id" class="input input-bordered w-full @error('tawk_property_id') input-error @enderror" value="{{ old('tawk_property_id', $settings->tawk_property_id) }}" placeholder="67d1234567890abcdef1234">@error('tawk_property_id')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror</div>
                                        <div class="form-control"><label class="label"><span class="label-text font-medium">Tawk Widget ID</span></label><input type="text" name="tawk_widget_id" class="input input-bordered w-full @error('tawk_widget_id') input-error @enderror" value="{{ old('tawk_widget_id', $settings->tawk_widget_id) }}" placeholder="1i1234567">@error('tawk_widget_id')<label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror</div>
                                    </div>
                                </div>
                                <div class="bg-base-200 p-6 rounded-lg text-sm text-base-content/80">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li>Homepage widget embed already implementation-এ wired আছে।</li>
                                        <li>Property ID আর Widget ID filled থাকলেই script load হবে।</li>
                                    </ul>
                                </div>
                                <div class="flex justify-center"><button type="submit" class="btn btn-primary btn-lg">Save Tawk Chat Credentials</button></div>
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
                    <li><details open><summary><span class="sidebar-text">Settings</span></summary><ul><li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li><li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li><li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li><li><a href="{{ route('admin.firebase.config') }}">Firebase Credentials</a></li><li><a href="{{ route('admin.google.otp.config') }}">Google OTP</a></li><li><a href="{{ route('admin.recaptcha.config') }}">reCAPTCHA Credentials</a></li><li><a href="{{ route('admin.tawk.config') }}" class="active">Tawk Chat Credentials</a></li></ul></details></li>
                    <li><form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">Logout</button></form></li>
                </ul>
            </aside>
        </div>
    </div>
</body>
</html>