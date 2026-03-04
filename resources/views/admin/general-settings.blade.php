<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'General Settings' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Settings</a></div>
            </div>

            <div class="p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-3xl font-bold">Homepage Content Settings</h2>
                                @if(session('success'))
                                    <div class="badge badge-success badge-lg">{{ session('success') }}</div>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('admin.homepage.update') }}" enctype="multipart/form-data" class="space-y-6">
                                @csrf
                                
                                <!-- Company Info Section -->
                                <div class="bg-base-200 p-6 rounded-lg">
                                    <h3 class="text-xl font-semibold mb-4">Company Information</h3>
                                    <div class="space-y-4">
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Page Title</span></label>
                                            <input type="text" name="page_title" class="input input-bordered w-full" value="{{ old('page_title', $settings->page_title) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Company Name</span></label>
                                            <input type="text" name="company_name" class="input input-bordered w-full" value="{{ old('company_name', $settings->company_name) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Company Logo</span></label>
                                            <input type="file" name="company_logo" class="file-input file-input-bordered w-full" accept="image/*">
                                            @if($settings->company_logo_url)
                                                <div class="mt-2">
                                                    <img src="{{ asset($settings->company_logo_url) }}" alt="Logo" class="h-16">
                                                </div>
                                            @endif
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Favicon</span></label>
                                            <input type="file" name="favicon" class="file-input file-input-bordered w-full" accept="image/x-icon,image/png">
                                            @if($settings->favicon_path)
                                                <div class="mt-2">
                                                    <img src="{{ asset($settings->favicon_path) }}" alt="Favicon" class="h-8">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Operators Section -->
                                <div class="bg-base-200 p-6 rounded-lg">
                                    <h3 class="text-xl font-semibold mb-4">Operators Section</h3>
                                    <div class="space-y-4">
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Section Title</span></label>
                                            <input type="text" name="operators_title" class="input input-bordered w-full" value="{{ old('operators_title', $settings->operators_title) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Section Subtitle</span></label>
                                            <textarea name="operators_subtitle" class="textarea textarea-bordered w-full" rows="2">{{ old('operators_subtitle', $settings->operators_subtitle) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Features Section -->
                                <div class="bg-base-200 p-6 rounded-lg">
                                    <h3 class="text-xl font-semibold mb-4">Features Section</h3>
                                    <div class="space-y-4">
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Section Title</span></label>
                                            <input type="text" name="features_title" class="input input-bordered w-full" value="{{ old('features_title', $settings->features_title) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Section Subtitle</span></label>
                                            <textarea name="features_subtitle" class="textarea textarea-bordered w-full" rows="2">{{ old('features_subtitle', $settings->features_subtitle) }}</textarea>
                                        </div>

                                        <div class="divider">Feature 1</div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Title</span></label>
                                            <input type="text" name="feature1_title" class="input input-bordered w-full" value="{{ old('feature1_title', $settings->feature1_title) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Description</span></label>
                                            <textarea name="feature1_description" class="textarea textarea-bordered w-full" rows="2">{{ old('feature1_description', $settings->feature1_description) }}</textarea>
                                        </div>

                                        <div class="divider">Feature 2</div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Title</span></label>
                                            <input type="text" name="feature2_title" class="input input-bordered w-full" value="{{ old('feature2_title', $settings->feature2_title) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Description</span></label>
                                            <textarea name="feature2_description" class="textarea textarea-bordered w-full" rows="2">{{ old('feature2_description', $settings->feature2_description) }}</textarea>
                                        </div>

                                        <div class="divider">Feature 3</div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Title</span></label>
                                            <input type="text" name="feature3_title" class="input input-bordered w-full" value="{{ old('feature3_title', $settings->feature3_title) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Description</span></label>
                                            <textarea name="feature3_description" class="textarea textarea-bordered w-full" rows="2">{{ old('feature3_description', $settings->feature3_description) }}</textarea>
                                        </div>

                                        <div class="divider">Feature 4</div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Title</span></label>
                                            <input type="text" name="feature4_title" class="input input-bordered w-full" value="{{ old('feature4_title', $settings->feature4_title) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Description</span></label>
                                            <textarea name="feature4_description" class="textarea textarea-bordered w-full" rows="2">{{ old('feature4_description', $settings->feature4_description) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistics Section -->
                                <div class="bg-base-200 p-6 rounded-lg">
                                    <h3 class="text-xl font-semibold mb-4">Statistics</h3>
                                    <div class="space-y-4">
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Customers Value</span></label>
                                            <input type="text" name="stats_customers_value" class="input input-bordered w-full" value="{{ old('stats_customers_value', $settings->stats_customers_value) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Customers Label</span></label>
                                            <input type="text" name="stats_customers_label" class="input input-bordered w-full" value="{{ old('stats_customers_label', $settings->stats_customers_label) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Recharged Value</span></label>
                                            <input type="text" name="stats_recharged_value" class="input input-bordered w-full" value="{{ old('stats_recharged_value', $settings->stats_recharged_value) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Recharged Label</span></label>
                                            <input type="text" name="stats_recharged_label" class="input input-bordered w-full" value="{{ old('stats_recharged_label', $settings->stats_recharged_label) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Operators Value</span></label>
                                            <input type="text" name="stats_operators_value" class="input input-bordered w-full" value="{{ old('stats_operators_value', $settings->stats_operators_value) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Operators Label</span></label>
                                            <input type="text" name="stats_operators_label" class="input input-bordered w-full" value="{{ old('stats_operators_label', $settings->stats_operators_label) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Service Value</span></label>
                                            <input type="text" name="stats_service_value" class="input input-bordered w-full" value="{{ old('stats_service_value', $settings->stats_service_value) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Service Label</span></label>
                                            <input type="text" name="stats_service_label" class="input input-bordered w-full" value="{{ old('stats_service_label', $settings->stats_service_label) }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Section -->
                                <div class="bg-base-200 p-6 rounded-lg">
                                    <h3 class="text-xl font-semibold mb-4">Footer Information</h3>
                                    <div class="space-y-4">
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Company Name</span></label>
                                            <input type="text" name="footer_company_name" class="input input-bordered w-full" value="{{ old('footer_company_name', $settings->footer_company_name) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Description</span></label>
                                            <textarea name="footer_description" class="textarea textarea-bordered w-full" rows="3">{{ old('footer_description', $settings->footer_description) }}</textarea>
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Address</span></label>
                                            <input type="text" name="footer_address" class="input input-bordered w-full" value="{{ old('footer_address', $settings->footer_address) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Phone</span></label>
                                            <input type="text" name="footer_phone" class="input input-bordered w-full" value="{{ old('footer_phone', $settings->footer_phone) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Email</span></label>
                                            <input type="email" name="footer_email" class="input input-bordered w-full" value="{{ old('footer_email', $settings->footer_email) }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- Social Media Section -->
                                <div class="bg-base-200 p-6 rounded-lg">
                                    <h3 class="text-xl font-semibold mb-4">Social Media Links</h3>
                                    <div class="space-y-4">
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">WhatsApp URL</span></label>
                                            <input type="url" name="social_whatsapp_url" class="input input-bordered w-full" value="{{ old('social_whatsapp_url', $settings->social_whatsapp_url) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">YouTube URL</span></label>
                                            <input type="url" name="social_youtube_url" class="input input-bordered w-full" value="{{ old('social_youtube_url', $settings->social_youtube_url) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Shopee URL</span></label>
                                            <input type="url" name="social_shopee_url" class="input input-bordered w-full" value="{{ old('social_shopee_url', $settings->social_shopee_url) }}">
                                        </div>
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Telegram URL</span></label>
                                            <input type="url" name="social_telegram_url" class="input input-bordered w-full" value="{{ old('social_telegram_url', $settings->social_telegram_url) }}">
                                        </div>

                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-medium">Messenger URL</span></label>
                                            <input type="url" name="social_messenger_url" class="input input-bordered w-full" value="{{ old('social_messenger_url', $settings->social_messenger_url) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-center pt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">Save All Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
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
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7l4-4m0 0l4 4m-4-4v18" />
                            </svg>
                            Pending Request
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
                                <li><a>All History</a></li>
                                <li><a>Flexiload</a></li>
                                <li><a>Drive</a></li>
                                <li><a>Internet Pack</a></li>
                                <li><a>Bkash</a></li>
                                <li><a>Nagad</a></li>
                                <li><a>Rocket</a></li>
                                <li><a>Success Refund</a></li>
                                <li><a>Islami Bank</a></li>
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
                                <li><a>Success Refund</a></li>
                                <li><a>Islami Bank</a></li>
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
                                <li><a>Operator Add</a></li>
                                <li><a>Regular Package</a></li>
                                <li><a>Drive Package</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Card Management
                            </summary>
                            <ul>
                                <li><a>Card Add</a></li>
                                <li><a>Card History</a></li>
                                <li><a>Card Management</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Bill Pay
                            </summary>
                            <ul>
                                <li><a>Pending Request</a></li>
                                <li><a>Bill Pay History</a></li>
                                <li><a>Bill Pay Settings</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Banking
                            </summary>
                            <ul>
                                <li><a>Pending Request</a></li>
                                <li><a>Bank History</a></li>
                                <li><a>Bank Settings</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4" />
                            </svg>
                            Sub Admin
                        </a>
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
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Payment History
                        </a>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Reports
                            </summary>
                            <ul>
                                <li><a>Receive reports</a></li>
                                <li><a>Balance Reports</a></li>
                                <li><a>Operator Reports</a></li>
                                <li><a>Daily Reports</a></li>
                                <li><a>Total usages</a></li>
                                <li><a>Transaction</a></li>
                                <li><a>Trnx ID</a></li>
                                <li><a>Sales Report</a></li>
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
                                <li><a>Api Settings</a></li>
                                <li><a>Payment Getway</a></li>
                                <li><a>Security Settings</a></li>
                                <li><a>Deleted Accounts</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Tools
                            </summary>
                            <ul>
                                <li><a>Branding</a></li>
                                <li><a>Device Logs</a></li>
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
                                <li><a>My Profile</a></li>
                                <li><a>Google OTP</a></li>
                                <li><a>Email/Mobile OTP</a></li>
                                <li><a>Manage Admin Users</a></li>
                                <li><a>Change PIN</a></li>
                                <li><a>Change Password</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Complain
                        </a>
                    </li>
                    <li>
                        <a>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Support
                        </a>
                    </li>
                    <li>
                        <details open>
                            <summary>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="sidebar-text">Settings</span>
                            </summary>
                            <ul>
                                <li><a href="{{ route('admin.homepage.edit') }}" class="active">General Settings</a></li>
                                <li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li>
                                <li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li>
                                <li><a>Google OTP</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
    <script>
        const drawer = document.getElementById('my-drawer');
        const sidebar = document.getElementById('sidebar');
        let isCollapsed = false;

        drawer.addEventListener('change', function() {
            if (window.innerWidth >= 1024) {
                if (isCollapsed) {
                    sidebar.classList.remove('w-16');
                    sidebar.classList.add('w-64');
                    document.querySelectorAll('.sidebar-text').forEach(el => el.classList.remove('hidden'));
                    isCollapsed = false;
                } else {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-16');
                    document.querySelectorAll('.sidebar-text').forEach(el => el.classList.add('hidden'));
                    isCollapsed = true;
                }
                drawer.checked = true;
            }
        });
    </script>
</body>
</html>
