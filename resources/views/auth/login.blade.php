@php
$recaptchaEnabled = optional($settings)->security_recaptcha === 'enable'
&& filled(optional($settings)->recaptcha_site_key)
&& filled(optional($settings)->recaptcha_secret_key);
@endphp
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Login' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
    <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @if($recaptchaEnabled)
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        @endif
                    </a>
                    <h2 class="text-2xl font-bold">User Login</h2>
                    <p class="text-base-content/60 mt-1">
                        {{ optional($settings)->company_name ?? 'Enter your credentials to access dashboard' }}
                    </p>
                </div>

                <form action="/login" method="POST">
                    @csrf
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Email Address</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="user@example.com" class="input input-bordered w-full" required />
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Password</span>
                        </label>
                        <div class="relative">
                            <input id="login-password" type="password" name="password" placeholder="••••••••" class="input input-bordered w-full pr-12" required />
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-base-content/60 transition hover:text-base-content" data-password-toggle="login-password" aria-label="Show password" aria-pressed="false">
                                <svg data-password-toggle-show-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                                </svg>
                                <svg data-password-toggle-hide-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="hidden h-5 w-5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A3.75 3.75 0 0 0 15.42 15.42" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 4.88c6 0 9.75 7.12 9.75 7.12a18.78 18.78 0 0 1-4.04 4.94" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61A18.73 18.73 0 0 0 2.25 12s3.75 6.75 9.75 6.75a10.7 10.7 0 0 0 2.53-.3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">PIN</span>
                        </label>
                        <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="4"
                            placeholder="4 digit PIN" class="input input-bordered w-full" required />
                    </div>

                    @if(filled($captchaQuestion ?? null))
                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Security Check</span>
                        </label>
                        <div class="text-sm text-base-content/70 mb-2">{{ $captchaQuestion }}</div>
                        <input type="text" name="captcha" value="{{ old('captcha') }}" placeholder="Enter answer" class="input input-bordered w-full @error('captcha') input-error @enderror" required />
                    </div>
                    @endif

                    @if(optional($settings)->google_otp_enabled)
                    <div class="alert alert-info mt-4 text-sm">
                        <span>If Google OTP is enabled on your account, you will verify the 6 digit code on the next page after password and PIN.</span>
                    </div>
                    @endif

                    @if($recaptchaEnabled)
                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Google reCAPTCHA</span>
                        </label>
                        <div class="g-recaptcha" data-sitekey="{{ $settings->recaptcha_site_key }}"></div>
                    </div>
                    @endif

                    @error('email')
                    <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror

                    @error('pin')
                    <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror

                    @error('captcha')
                    <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror

                    @error('g-recaptcha-response')
                    <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror

                    <div class="form-control mt-6">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm" @checked(old('remember')) />
                            <span class="label-text">Remember me</span>
                        </label>
                    </div>

                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary w-full">Sign In</button>
                    </div>

                    <div class="mt-2 text-center text-xs text-base-content/60">
                        Device IP: {{ $ip }}
                    </div>

                    <div class="form-control mt-3 text-center">
                        <a href="{{ route('forgot.password') }}" class="link link-hover text-sm font-medium">Forgot password?</a>
                    </div>
                </form>
                <div class="mt-3 text-muted text-center" style="font-size: 14px;">
                    {{ $browser }} &rarr;
                </div>

                <div class="divider">OR</div>

                <div class="text-center">
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