<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Codecartel Telecom - Mobile Recharge Bangladesh' }}</title>
    @if(optional($settings)->favicon_path)
    <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .hero-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0f172a 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .operator-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .pulse-glow {
            animation: pulse-glow 2s infinite;
        }

        .notice-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .notice-card {
            background: white;
            padding: 30px;
            border-radius: 1.5rem;
            max-width: 450px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 2px solid #3b82f6;
            animation: modalBounce 0.4s ease-out;
        }

        @keyframes modalBounce {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .notice-btn {
            background: #3b82f6;
            color: white;
            padding: 10px 40px;
            border-radius: 9999px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .notice-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
            }

            50% {
                box-shadow: 0 0 40px rgba(59, 130, 246, 0.8);
            }
        }

        .social-btn {
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: translateY(-4px) scale(1.1);
        }

        .whatsapp:hover {
            background-color: #25D366;
            color: white;
        }

        .youtube:hover {
            background-color: #FF0000;
            color: white;
        }

        .shopee:hover {
            background-color: #FF5722;
            color: white;
        }

        .telegram:hover {
            background-color: #0088CC;
            color: white;
        }

        .messenger:hover {
            background-color: #0084FF;
            color: white;
        }
    </style>
</head>

<body class="min-h-screen bg-base-100">

    @if(filled($loginNotice))
    <div id="loginNoticeModal" class="notice-overlay">
        <div class="notice-card">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">New Notice</h2>
            <p class="text-gray-600 text-lg leading-relaxed">
                {{ $loginNotice }}
            </p>
            <button onclick="closeNotice()" class="notice-btn">
                OK, I Understand
            </button>
        </div>
    </div>

    <script>
        function closeNotice() {
            const modal = document.getElementById('loginNoticeModal');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    </script>
    @endif


    <!-- Navigation -->
    <nav class="navbar bg-base-100 shadow-lg sticky top-0 z-50 border-b border-base-200">
        <div class="container mx-auto flex flex-wrap items-center justify-between px-4">
            <div class="flex items-center gap-2">
                <a href="/" class="flex items-center gap-2">
                    @if(optional($settings)->company_logo_url)
                    <img src="{{ asset($settings->company_logo_url) }}" alt="Logo" class="w-10 h-10 rounded-lg object-contain bg-base-100">
                    @else
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    @endif
                    <span class="text-xl font-bold text-primary">
                        {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}
                    </span>
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <div class="lg:hidden">
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </div>
                    <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                        <li><a href="#home" class="font-medium">Home</a></li>
                        <li><a href="#operators" class="font-medium">Operators</a></li>
                        <li><a href="#features" class="font-medium">Features</a></li>
                        <li><a href="#contact" class="font-medium">Contact</a></li>
                        <li><a href="#docs" class="font-medium">Docs</a></li>
                    </ul>
                </div>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-6">
                <ul class="menu menu-horizontal px-1 gap-1">
                    <li><a href="#home" class="font-medium hover:text-primary">Home</a></li>
                    <li><a href="#operators" class="font-medium hover:text-primary">Operators</a></li>
                    <li><a href="#features" class="font-medium hover:text-primary">Features</a></li>
                    <li><a href="#contact" class="font-medium hover:text-primary">Contact</a></li>
                    <li><a href="#docs" class="font-medium hover:text-primary">Docs</a></li>
                </ul>
                <div class="flex items-center gap-2">
                    <a href="/login" class="btn btn-ghost btn-sm">Login</a>
                    <a href="/register" class="btn btn-primary btn-sm">Sign Up</a>

                </div>
            </div>
        </div>
    </nav>



    <!-- Telecom Operators Section -->
    <section id="operators" class="py-20 bg-base-200">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    {{ optional($settings)->operators_title ?? 'Supported Telecom Operators' }}
                </h2>
                <p class="text-base-content/70 max-w-2xl mx-auto">
                    {{ optional($settings)->operators_subtitle ?? 'We support all major mobile network operators in Bangladesh for instant recharge' }}
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($operators as $operator)
                <div class="operator-card card bg-base-100 shadow-xl transition-all duration-300 cursor-pointer">
                    <div class="card-body items-center text-center p-6">
                        @php
                        $operatorLogo = $operator->logo_image_url ?: $operator->logo;
                        if ($operatorLogo && !\Illuminate\Support\Str::startsWith($operatorLogo, ['http://', 'https://', '//', 'data:'])) {
                        $operatorLogo = asset($operatorLogo);
                        }
                        @endphp
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3"
                            style="background-color: {{ $operator->circle_bg_color }};">
                            @if($operatorLogo)
                            <img src="{{ $operatorLogo }}" alt="{{ $operator->name }}" class="w-10 h-10 rounded-full object-contain bg-base-100">
                            @else
                            <span class="text-white font-bold text-xl">
                                {{ $operator->logo_text ?? $operator->short_code ?? substr($operator->name,0,2) }}
                            </span>
                            @endif
                        </div>
                        <h3 class="font-bold">{{ $operator->name }}</h3>
                        @if($operator->description)
                        <p class="text-xs text-base-content/60">{{ $operator->description }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-base-100">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    {{ optional($settings)->features_title ?? 'Why Choose Codecartel Telecom?' }}
                </h2>
                <p class="text-base-content/70 max-w-2xl mx-auto">
                    {{ optional($settings)->features_subtitle ?? 'Experience the best online recharge service in Bangladesh' }}
                </p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Feature 1 -->
                <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="card-body items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="card-title font-bold">
                            {{ optional($settings)->feature1_title ?? 'Instant Recharge' }}
                        </h3>
                        <p class="text-base-content/70">
                            {{ optional($settings)->feature1_description ?? 'Get your mobile recharge done instantly within seconds, 24/7 service available.' }}
                        </p>
                    </div>
                </div>
                <!-- Feature 2 -->
                <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="card-body items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="card-title font-bold">
                            {{ optional($settings)->feature2_title ?? 'Secure Payment' }}
                        </h3>
                        <p class="text-base-content/70">
                            {{ optional($settings)->feature2_description ?? '100% secure payment with bKash, Nagad, Card, and multiple payment options.' }}
                        </p>
                    </div>
                </div>
                <!-- Feature 3 -->
                <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="card-body items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-accent/10 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="card-title font-bold">
                            {{ optional($settings)->feature3_title ?? 'Best Rates' }}
                        </h3>
                        <p class="text-base-content/70">
                            {{ optional($settings)->feature3_description ?? 'Get the best recharge rates and exclusive offers on all operators.' }}
                        </p>
                    </div>
                </div>
                <!-- Feature 4 -->
                <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="card-body items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-info/10 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="card-title font-bold">
                            {{ optional($settings)->feature4_title ?? '24/7 Support' }}
                        </h3>
                        <p class="text-base-content/70">
                            {{ optional($settings)->feature4_description ?? 'Round the clock customer support for any recharge assistance.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-primary text-primary-content">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl md:text-5xl font-bold">
                        {{ optional($settings)->stats_customers_value ?? '50K+' }}
                    </div>
                    <div class="text-primary-content/80 mt-2">
                        {{ optional($settings)->stats_customers_label ?? 'Happy Customers' }}
                    </div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold">
                        {{ optional($settings)->stats_recharged_value ?? '৳10M+' }}
                    </div>
                    <div class="text-primary-content/80 mt-2">
                        {{ optional($settings)->stats_recharged_label ?? 'Recharged Monthly' }}
                    </div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold">
                        {{ optional($settings)->stats_operators_value ?? '6' }}
                    </div>
                    <div class="text-primary-content/80 mt-2">
                        {{ optional($settings)->stats_operators_label ?? 'Mobile Operators' }}
                    </div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold">
                        {{ optional($settings)->stats_service_value ?? '24/7' }}
                    </div>
                    <div class="text-primary-content/80 mt-2">
                        {{ optional($settings)->stats_service_label ?? 'Service Available' }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="docs" class="py-20 bg-base-100">
        <div class="container mx-auto px-4 space-y-8">
            <div class="text-center max-w-3xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Simple Provider API Documentation</h2>
                <p class="text-base-content/70">Client panel integration-er jonno quick copy-paste sample format.</p>
            </div>

            @include('partials.provider-api-docs-content', ['apiDocs' => $apiDocs])
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer footer-center p-10 bg-base-300 text-base-content">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 w-full max-w-6xl">
                <!-- Company Info -->
                <div class="lg:col-span-2">
                    <div class="flex items-center justify-center lg:justify-start gap-2 mb-4">
                        @if(optional($settings)->company_logo_url)
                        <img src="{{ asset($settings->company_logo_url) }}" alt="{{ optional($settings)->company_name ?? 'Codecartel Telecom' }}" class="w-10 h-10 rounded-lg object-contain bg-base-100">
                        @else
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        @endif
                        <span class="text-xl font-bold">
                            {{ optional($settings)->footer_company_name ?? 'Codecartel' }} <span class="text-primary">Telecom</span>
                        </span>
                    </div>
                    <p class="max-w-md text-center lg:text-left text-base-content/70">
                        {{ optional($settings)->footer_description ?? 'Your trusted online mobile recharge service in Bangladesh. Fast, secure, and reliable recharge for all major telecom operators.' }}
                    </p>

                    <!-- Social Links -->
                    <div class="flex justify-center lg:justify-start gap-3 mt-6">
                        <a href="{{ optional($settings)->social_whatsapp_url ?? 'https://wa.me/8801626984029' }}" target="_blank" class="btn btn-circle btn-sm bg-[#25D366] text-white border-none hover:bg-[#25D366]/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                        </a>
                        <a href="{{ optional($settings)->social_youtube_url ?? 'https://youtube.com/@codecartel' }}" target="_blank" class="btn btn-circle btn-sm bg-[#FF0000] text-white border-none hover:bg-[#FF0000]/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                            </svg>
                        </a>
                        <a href="{{ optional($settings)->social_shopee_url ?? 'https://shopee.com/codecartel' }}" target="_blank" class="btn btn-circle btn-sm bg-[#FF5722] text-white border-none hover:bg-[#FF5722]/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M16.925 3.735a2.992 2.992 0 0 1 2.084-.846h2.205a3.022 3.022 0 0 1 2.108 3.022v2.46l-.585-.293a1.142 1.142 0 0 0-.683-.195H3.332a2.05 2.05 0 0 0-1.877 1.059L0 12.451v.049l.797.398a2.95 2.95 0 0 1 1.476.977l7.235 3.618a2.965 2.965 0 0 1 .88 1.304v2.166a3.022 3.022 0 0 1-3.022 3.022h-2.46a3.022 3.022 0 0 1-3.022-3.022v-2.048l-.366.122a1.142 1.142 0 0 0-.586.512l-2.47 4.94h3.608l7.195-3.584 1.367-2.813a2.992 2.992 0 0 1-.146-1.134V6.926a3.022 3.022 0 0 1-.195-2.955v-.098l3.178 1.583v-2.46l-1.465-.733a.978.978 0 0 0-.635-.195h-5.74l-1.221-2.033h2.704z" />
                            </svg>
                        </a>
                        <a href="{{ optional($settings)->social_telegram_url ?? 'https://t.me/codecartel' }}" target="_blank" class="btn btn-circle btn-sm bg-[#0088CC] text-white border-none hover:bg-[#0088CC]/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                            </svg>
                        </a>
                        <a href="{{ optional($settings)->social_messenger_url ?? 'https://m.me/codecartel' }}" target="_blank" class="btn btn-circle btn-sm bg-[#0084FF] text-white border-none hover:bg-[#0084FF]/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738.098.119.112.224.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="footer-title font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="/" class="link link-hover">Home</a></li>
                        <li><a href="#operators" class="link link-hover">Operators</a></li>
                        <li><a href="#features" class="link link-hover">Features</a></li>
                        <li><a href="#contact" class="link link-hover">Contact</a></li>
                        <li><a href="/login" class="link link-hover">Login</a></li>
                        <li><a href="/register" class="link link-hover">Sign Up</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="footer-title font-bold mb-4">Contact Us</h3>
                    <ul class="space-y-2 text-base-content/80">
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ optional($settings)->footer_address ?? 'Dhaka, Bangladesh' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ optional($settings)->footer_phone ?? '+8801626984029' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ optional($settings)->footer_email ?? 'support@codecartel.com' }}
                        </li>
                    </ul>

                    <div class="mt-4">
                        <h4 class="font-bold mb-2">Payment Methods</h4>
                        <div class="flex flex-wrap gap-2">
                            <span class="badge badge-outline">bKash</span>
                            <span class="badge badge-outline">Nagad</span>
                            <span class="badge badge-outline">Rocket</span>

                        </div>
                    </div>
                </div>
            </div>

            <div class="divider mt-8"></div>
            <div class="text-center text-base-content/60">
                <p>&copy; 2024 Codecartel Telecom. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>

</html>