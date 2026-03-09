<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
    <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                    <h2 class="text-2xl font-bold">{{ $heading }}</h2>
                    <p class="text-base-content/60 mt-1">{{ $description }}</p>
                    @if(filled($pendingEmail))
                    <p class="text-sm text-base-content/70 mt-2">Account: {{ $pendingEmail }}</p>
                    @endif
                </div>

                <form action="{{ $formAction }}" method="POST">
                    @csrf

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Google Authenticator OTP</span>
                        </label>
                        <input type="text" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                            value="{{ old('otp') }}" placeholder="6 digit OTP" class="input input-bordered w-full @error('otp') input-error @enderror" required />
                    </div>

                    @error('otp')
                    <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror

                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary w-full">{{ $submitLabel }}</button>
                    </div>

                    <div class="mt-3 text-center text-xs text-base-content/60">
                        Device IP: {{ $ip }}
                    </div>

                    @if(filled($browser))
                    <div class="mt-2 text-center text-xs text-base-content/60">
                        {{ $browser }}
                    </div>
                    @endif

                    <div class="form-control mt-4 text-center">
                        <a href="{{ $backUrl }}" class="link link-hover text-sm font-medium">{{ $backLabel }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>