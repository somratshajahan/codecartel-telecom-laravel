<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firebase Credentials - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Firebase</a></div>
            </div>
            <div class="p-6">
                <div class="max-w-5xl mx-auto">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body space-y-6">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-3xl font-bold">🔔 Firebase Credentials</h2>
                                    <p class="text-sm opacity-70">Admin/user push notification চালু করার জন্য Firebase Web config + Service Account JSON save করুন।</p>
                                </div>
                                @if(session('success'))
                                    <div class="badge badge-success badge-lg">{{ session('success') }}</div>
                                @endif
                            </div>
                            @if($errors->any())
                                <div class="alert alert-error"><ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            <form method="POST" action="{{ route('admin.firebase.update') }}" class="space-y-6">
                                @csrf
                                <div class="bg-base-200 p-6 rounded-lg space-y-4">
                                    <h3 class="text-xl font-semibold">Firebase Web App Config</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-control"><label class="label"><span class="label-text font-medium">API Key</span></label><input type="text" name="firebase_api_key" class="input input-bordered w-full" value="{{ old('firebase_api_key', $settings->firebase_api_key) }}"></div>
                                        <div class="form-control"><label class="label"><span class="label-text font-medium">Auth Domain</span></label><input type="text" name="firebase_auth_domain" class="input input-bordered w-full" value="{{ old('firebase_auth_domain', $settings->firebase_auth_domain) }}" placeholder="project-id.firebaseapp.com"></div>
                                        <div class="form-control"><label class="label"><span class="label-text font-medium">Project ID</span></label><input type="text" name="firebase_project_id" class="input input-bordered w-full" value="{{ old('firebase_project_id', $settings->firebase_project_id) }}"></div>
                                        <div class="form-control"><label class="label"><span class="label-text font-medium">Storage Bucket</span></label><input type="text" name="firebase_storage_bucket" class="input input-bordered w-full" value="{{ old('firebase_storage_bucket', $settings->firebase_storage_bucket) }}"></div>
                                        <div class="form-control"><label class="label"><span class="label-text font-medium">Messaging Sender ID</span></label><input type="text" name="firebase_messaging_sender_id" class="input input-bordered w-full" value="{{ old('firebase_messaging_sender_id', $settings->firebase_messaging_sender_id) }}"></div>
                                        <div class="form-control"><label class="label"><span class="label-text font-medium">App ID</span></label><input type="text" name="firebase_app_id" class="input input-bordered w-full" value="{{ old('firebase_app_id', $settings->firebase_app_id) }}"></div>
                                    </div>
                                    <div class="form-control"><label class="label"><span class="label-text font-medium">Web Push VAPID Key</span></label><textarea name="firebase_vapid_key" rows="3" class="textarea textarea-bordered w-full">{{ old('firebase_vapid_key', $settings->firebase_vapid_key) }}</textarea></div>
                                </div>
                                <div class="bg-base-200 p-6 rounded-lg space-y-4">
                                    <h3 class="text-xl font-semibold">Firebase Service Account JSON</h3>
                                    <div class="alert alert-info text-sm">Firebase Console → Project Settings → Service accounts → Generate new private key. পুরো JSON এখানে paste করুন.</div>
                                    <div class="form-control"><label class="label"><span class="label-text font-medium">Service Account JSON</span></label><textarea name="firebase_service_account_json" rows="14" class="textarea textarea-bordered font-mono text-xs w-full" placeholder='{"type":"service_account",...}'>{{ old('firebase_service_account_json', $settings->firebase_service_account_json) }}</textarea></div>
                                </div>
                                <div class="flex justify-center"><button type="submit" class="btn btn-primary btn-lg">Save Firebase Credentials</button></div>
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
                    <li><details open><summary><span class="sidebar-text">Settings</span></summary><ul><li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li><li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li><li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li><li><a href="{{ route('admin.firebase.config') }}" class="active">Firebase Credentials</a></li><li><a href="{{ route('admin.google.otp.config') }}">Google OTP</a></li></ul></details></li>
                    <li><form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">Logout</button></form></li>
                </ul>
            </aside>
        </div>
    </div>
</body>
</html>