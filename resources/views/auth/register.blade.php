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
    <title>{{ optional($settings)->page_title ?? 'Register' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                    <h2 class="text-2xl font-bold">Create Account</h2>
                    <p class="text-base-content/60 mt-1">
                        {{ optional($settings)->company_name ?? 'Join Codecartel Telecom today' }}
                    </p>
                </div>

                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Full Name</span>
                        </label>
                        <input type="text" name="name" placeholder="Your full name" class="input input-bordered w-full" required />
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">User Name</span>
                        </label>
                        <input type="text" name="username" placeholder="Choose a username" class="input input-bordered w-full" required />
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">OTP Verification Method</span>
                        </label>
                        <select id="otp_method" class="select select-bordered w-full">
                            <option value="email">Email OTP</option>
                            <option value="mobile">Mobile OTP</option>
                        </select>
                    </div>

                    <div class="form-control mt-4" id="email_section">
                        <label class="label">
                            <span class="label-text font-medium">Email Address</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="email" name="email" id="email" placeholder="your@email.com" class="input input-bordered w-full" required />
                            <button type="button" onclick="sendOtp()" class="btn btn-primary btn-sm">Send OTP</button>
                        </div>
                        @if(session('otp_sent'))
                        <label class="label"><span class="label-text-alt text-success">{{ session('otp_sent') }}</span></label>
                        @endif
                    </div>

                    <div class="form-control mt-4" id="mobile_section" style="display:none;">
                        <label class="label">
                            <span class="label-text font-medium">Mobile Number</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="tel" name="mobile" id="mobile" placeholder="01XXXXXXXXX" pattern="01[0-9]{9}" class="input input-bordered w-full" />
                            <button type="button" onclick="sendMobileOtp()" class="btn btn-primary btn-sm">Send OTP</button>
                        </div>
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">OTP Code</span>
                        </label>
                        <input type="text" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" pattern="[0-9]{6}" class="input input-bordered w-full" required />
                        @error('otp')
                        <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                        @enderror
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">NID Number</span>
                        </label>
                        <input type="text" name="nid" placeholder="Enter your NID number" class="input input-bordered w-full" required />
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Level</span>
                        </label>
                        <select name="level" class="select select-bordered w-full" required>
                            <option value="house">House</option>
                            <option value="dgm">DGM</option>
                            <option value="dealer">Dealer</option>
                            <option value="seller">Seller</option>
                            <option value="retailer">Retailer</option>
                        </select>
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Referral Code (Optional)</span>
                        </label>
                        <input type="text" name="referral_code" placeholder="Enter referral code" class="input input-bordered w-full" value="{{ old('referral_code') }}" />
                        <label class="label">
                            <span class="label-text-alt text-base-content/60">Referral code শুধু registration-এর সময় একবারই use করা যাবে.</span>
                        </label>
                        @error('referral_code')
                        <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                        @enderror
                    </div>

                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Password</span>
                        </label>
                        <div class="relative">
                            <input id="register-password" type="password" name="password" placeholder="••••••••" class="input input-bordered w-full pr-12" required />
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-base-content/60 transition hover:text-base-content" data-password-toggle="register-password" aria-label="Show password" aria-pressed="false">
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
                            <span class="label-text font-medium">Confirm Password</span>
                        </label>
                        <div class="relative">
                            <input id="register-password-confirmation" type="password" name="password_confirmation" placeholder="••••••••" class="input input-bordered w-full pr-12" required />
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-base-content/60 transition hover:text-base-content" data-password-toggle="register-password-confirmation" aria-label="Show password" aria-pressed="false">
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
                        <input type="text" name="pin" placeholder="0000" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" class="input input-bordered w-full text-center tracking-widest" required />
                        <label class="label">
                            <span class="label-text-alt text-xs">4-digit PIN only</span>
                        </label>
                    </div>

                    @if($recaptchaEnabled)
                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Google reCAPTCHA</span>
                        </label>
                        <div class="g-recaptcha" data-sitekey="{{ $settings->recaptcha_site_key }}"></div>
                        @error('g-recaptcha-response')
                        <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                        @enderror
                    </div>
                    @endif

                    <div class="form-control mt-6">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" class="checkbox checkbox-primary checkbox-sm" required />
                            <span class="label-text">I agree to the <a href="/terms" class="link link-primary">Terms & Conditions</a></span>
                        </label>
                    </div>

                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary w-full">Create Account</button>
                    </div>
                </form>

                <div class="divider">OR</div>

                <div class="text-center">
                    <p class="text-base-content/70">
                        Already have an account?
                        <a href="/login" class="link link-primary font-medium">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Toggle OTP method
        document.getElementById('otp_method').addEventListener('change', function() {
            const method = this.value;
            const emailSection = document.getElementById('email_section');
            const mobileSection = document.getElementById('mobile_section');
            const emailInput = document.getElementById('email');
            const mobileInput = document.getElementById('mobile');

            if (method === 'email') {
                emailSection.style.display = 'block';
                mobileSection.style.display = 'none';
                emailInput.required = true;
                mobileInput.required = false;
            } else {
                emailSection.style.display = 'none';
                mobileSection.style.display = 'block';
                emailInput.required = false;
                mobileInput.required = true;
            }
        });

        function sendOtp() {
            const email = document.getElementById('email').value;
            if (!email) {
                alert('Please enter email address');
                return;
            }

            fetch('{{ route("send.registration.otp") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        email: email
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('OTP sent to your email!');
                    } else {
                        alert(data.message || 'Failed to send OTP');
                    }
                })
                .catch(error => {
                    alert('Error sending OTP');
                });
        }

        function sendMobileOtp() {
            const mobile = document.getElementById('mobile').value;
            if (!mobile) {
                alert('Please enter mobile number');
                return;
            }

            fetch('{{ route("send.registration.otp.mobile") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mobile: mobile
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('OTP sent to your mobile!');
                    } else {
                        alert(data.message || 'Failed to send OTP');
                    }
                })
                .catch(error => {
                    alert('Error sending OTP');
                });
        }
    </script>
</body>

</html>