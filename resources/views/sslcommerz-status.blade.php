<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSLCommerz Status - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
    <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html {
            background: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top, rgba(59, 130, 246, 0.16), transparent 32%),
                linear-gradient(135deg, #eff6ff 0%, #f8fafc 45%, #e2e8f0 100%);
        }
    </style>
</head>

<body class="min-h-screen px-4 py-8 text-slate-800">
    @php
    $statusClass = match ($status) {
    'approved' => 'alert-success',
    'failed', 'cancelled' => 'alert-error',
    default => 'alert-info',
    };

    $statusBadgeClass = match ($status) {
    'approved' => 'badge-success',
    'failed', 'cancelled' => 'badge-error',
    default => 'badge-info',
    };

    $statusIcon = match ($status) {
    'approved' => '✅',
    'cancelled' => '⚠️',
    default => '❌',
    };
    @endphp

    <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-3xl items-center justify-center">
        <div class="card w-full overflow-hidden border border-white/60 bg-white/90 shadow-2xl backdrop-blur">
            <div class="bg-gradient-to-r from-slate-900 via-blue-900 to-slate-800 px-6 py-8 text-white md:px-8">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <a href="{{ route('homepage') }}" class="flex items-center gap-3">
                        @if(optional($settings)->company_logo_url)
                        <img src="{{ asset($settings->company_logo_url) }}" alt="{{ optional($settings)->company_name ?? 'Codecartel Telecom' }}" class="h-14 w-14 rounded-2xl bg-white p-2 object-contain shadow-lg">
                        @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-2xl shadow-lg">📱</div>
                        @endif
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">Payment Update</p>
                            <h2 class="text-2xl font-bold">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</h2>
                        </div>
                    </a>

                    <div class="badge {{ $statusBadgeClass }} badge-lg border-none px-4 py-4 font-semibold text-white">
                        {{ $statusLabel }}
                    </div>
                </div>
            </div>

            <div class="card-body items-center space-y-5 p-6 text-center md:p-8">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-5xl shadow-inner">
                    {{ $statusIcon }}
                </div>

                <div>
                    <h1 class="text-3xl font-bold">SSLCommerz Payment {{ $statusLabel }}</h1>
                    <p class="mt-2 text-sm text-slate-500">Gateway callback complete হয়েছে। নিচে latest status দেখানো হচ্ছে।</p>
                </div>

                <div class="alert {{ $statusClass }} w-full text-left">
                    <span>{{ $message }}</span>
                </div>

                @if($transaction)
                <div class="grid w-full gap-3 text-left md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Transaction ID</p>
                        <p class="mt-2 break-all text-sm font-bold text-slate-800">{{ $transaction->tran_id }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                        <p class="mt-2 text-sm font-bold text-slate-800">{{ ucfirst((string) $transaction->status) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</p>
                        <p class="mt-2 text-sm font-bold text-slate-800">{{ number_format((float) $transaction->amount, 2) }} {{ $transaction->currency ?: 'BDT' }}</p>
                    </div>
                </div>
                @endif

                <div class="w-full rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-left text-sm text-slate-600">
                    <span class="font-semibold text-slate-800">Note:</span>
                    payment success হলে balance backend-এ process হয়ে গেছে। account-এ ঢুকে latest status check করতে পারবেন।
                </div>

                <div class="flex w-full flex-col justify-center gap-3 sm:flex-row">
                    @auth
                    @if($transaction && (int) auth()->id() === (int) $transaction->user_id && auth()->user()->hasPermission('add_balance'))
                    <a href="{{ route('user.add.balance') }}" class="btn btn-primary">Go to Add Balance</a>
                    @else
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                    @endif
                    @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                    <a href="{{ route('homepage') }}" class="btn btn-outline">Back to Home</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</body>

</html>