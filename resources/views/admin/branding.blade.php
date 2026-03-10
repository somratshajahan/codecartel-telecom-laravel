<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branding Settings | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-base-200">

    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col p-6">
            <div class="lg:hidden mb-4">
                <label for="my-drawer" class="btn btn-primary drawer-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </label>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <form id="brandingForm" action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                        @csrf

                        <div class="flex justify-between items-center border-b pb-4 mb-6">
                            <h2 class="card-title text-2xl font-bold text-primary"><i class="fas fa-copyright"></i> Branding & System Settings</h2>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>

                        @if (session('success'))
                        <div class="alert alert-success">
                            <span>{{ session('success') }}</span>
                        </div>
                        @endif

                        @if (session('warning'))
                        <div class="alert alert-warning">
                            <span>{{ session('warning') }}</span>
                        </div>
                        @endif

                        @if ($errors->any())
                        <div class="alert alert-error">
                            <span>{{ $errors->first() }}</span>
                        </div>
                        @endif

                        <section>
                            <h3 class="font-semibold text-lg mb-4 border-l-4 border-primary pl-2">General Info</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-bold">Brand Name</span></label>
                                    <input type="text" name="brand_name" value="{{ old('brand_name', $branding->brand_name ?? '') }}" class="input input-bordered w-full" placeholder="e.g. MyBrand" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-bold">Top Title</span></label>
                                    <input type="text" name="top_title" value="{{ old('top_title', $branding->top_title ?? '') }}" class="input input-bordered w-full" placeholder="e.g. Welcome to Admin" />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-bold">Footer</span></label>
                                    <input type="text" name="footer" value="{{ old('footer', $branding->footer ?? '') }}" class="input input-bordered w-full" placeholder="e.g. © 2024 All Rights Reserved" />
                                </div>
                            </div>
                        </section>
                        <section class="bg-base-200 p-6 rounded-xl shadow-inner">
                            <h3 class="font-bold text-lg mb-6 flex items-center gap-2 text-neutral">
                                <i class="fas fa-cogs text-primary"></i> System Configurations
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

                                <div class="form-control">
                                    <label class="label pt-0"><span class="label-text font-bold">Registration System</span></label>
                                    <div class="join w-full">
                                        <input class="join-item btn btn-sm flex-1" type="radio" name="registration_system" aria-label="Off" value="off" {{ ($branding->registration_system ?? 'off') == 'off' ? 'checked' : '' }} />
                                        <input class="join-item btn btn-sm flex-1" type="radio" name="registration_system" aria-label="On" value="on" {{ ($branding->registration_system ?? '') == 'on' ? 'checked' : '' }} />
                                    </div>
                                </div>

                                <div class="form-control">
                                    <label class="label pt-0"><span class="label-text font-bold">Drive System</span></label>
                                    <div class="join w-full">
                                        <input class="join-item btn btn-sm flex-1" type="radio" name="drive_system" aria-label="Auto" value="auto" {{ ($branding->drive_system ?? '') == 'auto' ? 'checked' : '' }} />
                                        <input class="join-item btn btn-sm flex-1" type="radio" name="drive_system" aria-label="Manual" value="manual" {{ ($branding->drive_system ?? 'manual') == 'manual' ? 'checked' : '' }} />
                                    </div>
                                </div>

                                <div class="form-control">
                                    <label class="label pt-0"><span class="label-text font-bold">Drive Balance</span></label>
                                    @php($selectedDriveBalance = old('drive_balance', $branding->drive_balance ?? 'on'))
                                    <div class="grid grid-cols-2 gap-3">
                                        <label for="drive_balance_off" class="flex cursor-pointer items-center justify-between rounded-lg border px-3 py-2 {{ $selectedDriveBalance === 'off' ? 'border-primary bg-primary/10' : 'border-base-300' }}">
                                            <span class="font-medium">Off</span>
                                            <input id="drive_balance_off" class="radio radio-primary" type="radio" name="drive_balance" value="off" {{ $selectedDriveBalance === 'off' ? 'checked' : '' }} />
                                        </label>
                                        <label for="drive_balance_on" class="flex cursor-pointer items-center justify-between rounded-lg border px-3 py-2 {{ $selectedDriveBalance === 'on' ? 'border-primary bg-primary/10' : 'border-base-300' }}">
                                            <span class="font-medium">On</span>
                                            <input id="drive_balance_on" class="radio radio-primary" type="radio" name="drive_balance" value="on" {{ $selectedDriveBalance === 'on' ? 'checked' : '' }} />
                                        </label>
                                    </div>
                                    <label class="label pb-0">
                                        <span class="label-text-alt">On = deduct from drive balance, Off = deduct from main balance</span>
                                    </label>
                                </div>

                                <div class="form-control">
                                    <label class="label pt-0"><span class="label-text font-bold">Modem Connection</span></label>
                                    <div class="join w-full">
                                        <input class="join-item btn btn-sm flex-1" type="radio" name="modem_connection" aria-label="Unlock" value="unlock" {{ ($branding->modem_connection ?? '') == 'unlock' ? 'checked' : '' }} />
                                        <input class="join-item btn btn-sm flex-1" type="radio" name="modem_connection" aria-label="Lock" value="lock" {{ ($branding->modem_connection ?? 'lock') == 'lock' ? 'checked' : '' }} />
                                    </div>
                                </div>

                                <div class="form-control">
                                    <label class="label pt-0"><span class="label-text font-bold">Modem Password</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                            <i class="fas fa-key text-xs"></i>
                                        </span>
                                        <input type="text"
                                            name="modem_pass"
                                            value="{{ $branding->modem_pass ?? '' }}"
                                            class="input input-bordered input-sm w-full pl-9 focus:border-primary"
                                            placeholder="Enter modem password" />
                                    </div>
                                </div>

                            </div>
                        </section>

                        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">SMS Provider</span></label>
                                @php($selectedSmsProvider = old('sms_provider', $branding->sms_provider ?? ''))
                                <select name="sms_provider" class="select select-bordered w-full">
                                    <option value="" {{ $selectedSmsProvider === '' ? 'selected' : '' }}>Select Sms Provider</option>
                                    <option value="Solutionsclan" {{ $selectedSmsProvider === 'Solutionsclan' ? 'selected' : '' }}>Solutionsclan</option>
                                    <option value="Flexisoftwarebd" {{ $selectedSmsProvider === 'Flexisoftwarebd' ? 'selected' : '' }}>Flexisoftwarebd</option>
                                    <option value="bulksmsbd" {{ $selectedSmsProvider === 'bulksmsbd' ? 'selected' : '' }}>bulksmsbd</option>
                                    <option value="SMSQ" {{ $selectedSmsProvider === 'SMSQ' ? 'selected' : '' }}>SMSQ</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Sms User</span></label>
                                <input type="text" name="sms_user" value="{{ old('sms_user', $branding->sms_user ?? '') }}" class="input input-bordered w-full" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Sms Password</span></label>
                                <input type="password" name="sms_pass" class="input input-bordered w-full" />
                            </div>
                        </section>

                        <section id="payment-gateway" class="grid grid-cols-1 md:grid-cols-4 gap-4 scroll-mt-24">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Bkash</span></label>
                                <input type="text" name="bkash" value="{{ old('bkash', $branding->bkash ?? '') }}" class="input input-bordered input-sm" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Rocket</span></label>
                                <input type="text" name="rocket" value="{{ old('rocket', $branding->rocket ?? '') }}" class="input input-bordered input-sm" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Nagad</span></label>
                                <input type="text" name="nagad" value="{{ old('nagad', $branding->nagad ?? '') }}" class="input input-bordered input-sm" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Upay</span></label>
                                <input type="text" name="upay" value="{{ old('upay', $branding->upay ?? '') }}" class="input input-bordered input-sm" />
                            </div>
                        </section>

                        <section class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">WhatsApp Link</span></label>
                                <input type="url" name="whatsapp" class="input input-bordered w-full" />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Telegram Link</span></label>
                                <input type="url" name="telegram" class="input input-bordered w-full" />
                            </div>
                        </section>

                        <section class="bg-base-200 p-6 rounded-xl shadow-inner">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="font-bold text-lg text-neutral">Slide Show Images</h3>
                                    <p class="text-sm text-base-content/70">Maximum 14 images. Only upload the slots you want to add or replace.</p>
                                </div>
                                <span class="badge badge-primary badge-outline">14 Slots</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="table table-zebra">
                                    <thead>
                                        <tr>
                                            <th>Slot</th>
                                            <th>Current Image</th>
                                            <th>Upload New Image</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for($slot = 1; $slot <= 14; $slot++)
                                            @php($existingSlide=($brandingSlides ?? collect())->get($slot))
                                            <tr>
                                                <td class="font-semibold">{{ $slot }}</td>
                                                <td>
                                                    @if($existingSlide && $existingSlide->image_path)
                                                    <img src="{{ asset('storage/' . $existingSlide->image_path) }}" alt="Slide {{ $slot }}" class="h-16 w-28 rounded-lg object-cover border border-base-300">
                                                    @else
                                                    <span class="text-sm text-base-content/60">No image uploaded</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="file" name="slides[{{ $slot }}]" accept="image/*" class="file-input file-input-bordered file-input-sm w-full max-w-xs" />
                                                    @error("slides.$slot")
                                                    <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                                                    @enderror
                                                </td>
                                            </tr>
                                            @endfor
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-secondary">Meta Description (SEO)</span></label>
                                <textarea name="meta_desc" class="textarea textarea-bordered h-24"></textarea>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-secondary">Keywords (SEO)</span></label>
                                <textarea name="keywords" class="textarea textarea-bordered h-24">{{ old('keywords', $branding->keywords ?? '') }}</textarea>
                            </div>
                        </section>
                    </form>
                </div>
            </div>
        </div>

        <div class="drawer-side z-40">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 min-h-screen border-r border-base-200 transition-all duration-300">
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
                    <li>
                        <a href="{{ route('admin.dashboard') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="sidebar-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.backup') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Backup
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.pending.drive.requests') }}" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7l4-4m0 0l4 4m-4-4v18" />
                                </svg>
                                <span>Pending Request</span>
                            </div>
                            @if(isset($pendingCount) && $pendingCount > 0)
                            <span class="badge badge-error badge-sm">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Recharge History
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.all.history') }}">All History</a></li>
                                <li><a>Flexiload</a></li>
                                <li><a href="{{ route('admin.drive.history') }}">Drive</a></li>
                                <li><a href="{{ route('admin.internet.history') }}">Internet Pack</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                Message Inbox
                            </summary>
                            <ul>
                                <li><a>Flexiload</a></li>
                                <li><a>Drive</a></li>
                                <li><a>Internet Pack</a></li>
                                <li><a>Bkash</a></li>
                                <li><a>Nagad</a></li>
                                <li><a>Rocket</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                Offer Settings
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.operator.create') }}">Add Operator</a></li>
                                <li><a href="{{ route('admin.regular.offer') }}">Regular Package</a></li>
                                <li><a href="{{ route('admin.drive.offer') }}">Drive Package</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Reseller
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.resellers') }}">All Reseller</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'house']) }}">House</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'dgm']) }}">DGM</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'dealer']) }}">Dealer</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'seller']) }}">Seller</a></li>
                                <li><a href="{{ route('admin.resellers', ['level' => 'retailer']) }}">Retailer</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                </svg>
                                Administration
                            </summary>
                            <ul>
                                <li><a>Service Modules</a></li>
                                <li><a>Rate Modules</a></li>
                                <li><a>Deposit</a></li>
                                <li><a>Modem List</a></li>
                                <li><a>Modem Device</a></li>
                                <li><a>Recharge Block List</a></li>
                                <li><a href="{{ route('api.index') }}">Api Settings</a></li>
                                <li><a href="{{ route('admin.payment.gateway') }}">Payment Gateway</a></li>
                                <li><a>Security Settings</a></li>
                                <li><a>Deleted Accounts</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details open>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Tools
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.branding') }}" class="active bg-primary text-primary-content">Branding</a></li>
                                <li><a href="{{ route('admin.device.logs') }}">Device Logs</a></li>
                                <li><a>Reseller Notice</a></li>
                                <li><a>Login Notice</a></li>
                                <li><a>Slides</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2h1a2 2 0 002-2v-1a2 2 0 012-2h1.945M7.757 15.757a3 3 0 104.486 0M12 10.5a3 3 0 110-6 3 3 0 010 6z" />
                                </svg>
                                Global
                            </summary>
                            <ul>
                                <li><a>Country</a></li>
                                <li><a>Operator</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Admin Account
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.profile') }}">My Profile</a></li>
                                <li><a href="/admin/manage-admins">Manage Admin Users</a></li>
                                <li><a href="{{ route('admin.change.credentials') }}">Change Password & PIN</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <a href="{{ route('admin.complaints') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Complain
                        </a>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="sidebar-text">Settings</span>
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li>
                                <li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li>
                                <li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left text-error">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
                <div class="p-4 mt-auto border-t border-base-200">
                    <a href="/" class="btn btn-outline btn-sm w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Back to Website
                    </a>
                </div>
            </aside>
        </div>

</body>

</html>