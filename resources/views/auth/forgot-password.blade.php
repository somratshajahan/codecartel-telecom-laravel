<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Forgot Password' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; }</style>
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
                    <h2 class="text-2xl font-bold">Forgot Password</h2>
                    <p class="text-base-content/60 mt-1">Enter your email to reset password</p>
                </div>
                
                <form action="{{ route('forgot.password.submit') }}" method="POST">
                    @csrf
                    <div class="form-control">
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
                            <span class="label-text font-medium">New Password</span>
                        </label>
                        <input type="password" name="password" placeholder="••••••••" class="input input-bordered w-full" required />
                    </div>
                    
                    <div class="form-control mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Confirm Password</span>
                        </label>
                        <input type="password" name="password_confirmation" placeholder="••••••••" class="input input-bordered w-full" required />
                    </div>

                    @error('email')
                        <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror
                    
                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary w-full">Reset Password</button>
                    </div>
                </form>
                
                <div class="divider">OR</div>
                
                <div class="text-center">
                    <p class="text-base-content/70">
                        Remember your password? 
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
            
            fetch('{{ route("send.forgot.password.otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: email })
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
            
            fetch('{{ route("send.forgot.password.otp.mobile") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ mobile: mobile })
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
