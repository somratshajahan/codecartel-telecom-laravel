<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flexiload - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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

<body class="min-h-screen bg-base-200 flex flex-col">
    <div class="drawer drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a href="{{ route('dashboard') }}" class="px-2 text-xl font-bold">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</a>
                </div>
                <div class="flex-none gap-2">
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
                                <span class="font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </div>
                        </div>
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            @if(Auth::user() && Auth::user()->hasPermission('profile'))
                            <li><a href="{{ route('user.profile') }}">Profile</a></li>
                            <li><a href="{{ route('user.profile') }}">Settings</a></li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <span class="font-semibold hidden sm:inline">{{ Auth::user()->name }}</span>
                </div>
            </div>

            <div class="container mx-auto p-6 flex-1">
                <div class="max-w-6xl mx-auto space-y-6">
                    <div class="text-center">
                        <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-800">Send Flexiload</h1>
                        <p class="text-slate-500 mt-2">Operator, number, amount submit kore latest 10 ta flexiload request dekhun.</p>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success shadow-sm">
                        <span>{{ session('success') }}</span>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-error shadow-sm">
                        <span>{{ session('error') }}</span>
                    </div>
                    @endif

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)] items-start">
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title text-2xl mb-2">Send Flexiload</h2>

                                <form method="POST" action="{{ route('user.flexi.store') }}" class="space-y-4">
                                    @csrf

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">Operator</span></label>
                                        <select id="operator" name="operator" class="select select-bordered w-full @error('operator') select-error @enderror">
                                            <option value="">Choice Operator</option>
                                            @foreach($operators as $operator)
                                            <option value="{{ $operator['route_name'] ?? $operator['name'] }}" {{ old('operator', data_get($selectedOperator, 'route_name') ?? data_get($selectedOperator, 'name')) == ($operator['route_name'] ?? $operator['name']) ? 'selected' : '' }}>
                                                {{ $operator['name'] }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <label class="label flex-col items-start gap-1">
                                            <span id="detectedOperatorText" class="label-text-alt text-primary {{ data_get($autoDetectedOperator, 'name') ? '' : 'hidden' }}">
                                                {{ data_get($autoDetectedOperator, 'name') ? 'Auto detected from number: ' . data_get($autoDetectedOperator, 'name') : '' }}
                                            </span>
                                            @error('operator')
                                            <span class="label-text-alt text-error">{{ $message }}</span>
                                            @enderror
                                        </label>
                                    </div>

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">Number</span></label>
                                        <input id="flexiNumber" name="number" type="text" value="{{ old('number') }}" placeholder="eg: 0171XXXXXXX" maxlength="11" inputmode="numeric" autocomplete="off" class="input input-bordered w-full @error('number') input-error @enderror" />
                                        <label class="label flex-col items-start gap-1">
                                            <span class="label-text-alt">[ Min Number 11, Max Number 11 ]</span>
                                            @error('number')
                                            <span class="label-text-alt text-error">{{ $message }}</span>
                                            @enderror
                                        </label>
                                    </div>

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">Amount</span></label>
                                        <input name="amount" type="number" value="{{ old('amount') }}" placeholder="eg: 100" min="10" max="1499" class="input input-bordered w-full @error('amount') input-error @enderror" />
                                        <label class="label flex-col items-start gap-1">
                                            <span class="label-text-alt">[ Min Amount 10, Max Amount 1499 ]</span>
                                            @error('amount')
                                            <span class="label-text-alt text-error">{{ $message }}</span>
                                            @enderror
                                        </label>
                                    </div>

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">Type</span></label>
                                        <select name="type" class="select select-bordered w-full @error('type') select-error @enderror">
                                            <option value="Prepaid" {{ old('type', 'Prepaid') === 'Prepaid' ? 'selected' : '' }}>Prepaid</option>
                                            <option value="Postpaid" {{ old('type') === 'Postpaid' ? 'selected' : '' }}>Postpaid</option>
                                        </select>
                                        @error('type')
                                        <label class="label">
                                            <span class="label-text-alt text-error">{{ $message }}</span>
                                        </label>
                                        @enderror
                                    </div>

                                    <div class="form-control">
                                        <label class="label"><span class="label-text font-semibold">User PIN</span></label>
                                        <input name="pin" type="password" placeholder="Enter 4 digit PIN" maxlength="4" inputmode="numeric" autocomplete="off" class="input input-bordered w-full @error('pin') input-error @enderror" />
                                        <label class="label flex-col items-start gap-1">
                                            <span class="label-text-alt">[ Enter your 4 digit PIN ]</span>
                                            @error('pin')
                                            <span class="label-text-alt text-error">{{ $message }}</span>
                                            @enderror
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-full">Send Flexiload</button>
                                </form>
                            </div>
                        </div>

                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title text-2xl mb-2">Last 10 Requests</h2>

                                <div class="overflow-x-auto">
                                    <table class="table table-zebra">
                                        <thead>
                                            <tr>
                                                <th>Number</th>
                                                <th>Amount</th>
                                                <th>Cost</th>
                                                <th>Trnx</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($flexiRequests as $requestItem)
                                            @php
                                            $statusClasses = match (strtolower((string) $requestItem->status)) {
                                            'approved', 'success' => 'badge-success',
                                            'rejected', 'failed', 'cancelled' => 'badge-error',
                                            default => 'badge-warning',
                                            };
                                            @endphp
                                            <tr>
                                                <td>{{ $requestItem->mobile }}</td>
                                                <td>{{ number_format((float) $requestItem->amount, 2) }}</td>
                                                <td>{{ number_format((float) $requestItem->cost, 2) }}</td>
                                                <td>{{ $requestItem->trnx_id ?: '-' }}</td>
                                                <td><span class="badge {{ $statusClasses }} capitalize">{{ $requestItem->status }}</span></td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-base-content/60 py-6">No Requests Found.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer items-center p-4 bg-base-300 text-base-content justify-center">
                <div class="items-center grid-flow-col">
                    <p>Copyright © 2026 - All right reserved by {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</p>
                </div>
            </footer>
        </div>

        <div class="drawer-side">
            <label for="my-drawer" class="drawer-overlay"></label>
            <ul class="menu p-4 w-60 min-h-full bg-base-100 text-base-content">
                <li><a href="{{ route('dashboard') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>Dashboard</a></li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>New Request</span></summary>
                        <ul class="p-2">
                            <li><a href="{{ route('user.flexi') }}">Flexiload</a></li>
                            <li><a href="{{ route('user.internet') }}">Internet Pack</a></li>
                            <li><a href="{{ route('user.drive') }}">Drive</a></li>
                            <li><a href="#">Bkash</a></li>
                            <li><a href="#">Nagad</a></li>
                            <li><a href="#">Rocket</a></li>
                            <li><a href="#">Upay</a></li>
                            <li><a href="#">Islami Bank</a></li>
                            <li><a href="{{ route('user.flexi') }}">Bulk Flexi</a></li>
                        </ul>
                    </details>
                </li>
                <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>Pending Request</a></li>
                <li>
                    <details>
                        <summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5M21 21v-5h-5M4 4l16 16" />
                                </svg>History</span></summary>
                        <ul class="p-2">
                            <li><a href="#">All history</a></li>
                            <li><a href="{{ route('user.flexi') }}">Flexiload</a></li>
                            <li><a href="#">Internet Pack</a></li>
                            <li><a href="#">Drive</a></li>
                            <li><a href="#">Bkash</a></li>
                            <li><a href="#">Nagad</a></li>
                            <li><a href="#">Rocket</a></li>
                            <li><a href="#">Upay</a></li>
                            <li><a href="#">Islami Bank</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex items-center gap-2 w-full"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>Logout</button></form>
                </li>
            </ul>
        </div>
    </div>
    <script id="flexi-operator-prefixes" type="application/json">
        @json($operatorPrefixes ?? [])
    </script>
    <script>
        (() => {
            const numberInput = document.getElementById('flexiNumber');
            const operatorSelect = document.getElementById('operator');
            const detectedOperatorText = document.getElementById('detectedOperatorText');
            const prefixesPayload = document.getElementById('flexi-operator-prefixes');

            if (!numberInput || !operatorSelect || !prefixesPayload) {
                return;
            }

            let operatorPrefixes = {};

            try {
                operatorPrefixes = JSON.parse(prefixesPayload.textContent || '{}');
            } catch (error) {
                operatorPrefixes = {};
            }

            const optionLabels = Array.from(operatorSelect.options).reduce((carry, option) => {
                if (option.value) {
                    carry[String(option.value).toLowerCase().replace(/[^a-z]/g, '')] = option.textContent.trim();
                }

                return carry;
            }, {});

            const detectOperator = (mobile) => {
                const prefix = mobile.slice(0, 3);

                return Object.entries(operatorPrefixes).find(([, prefixes]) => Array.isArray(prefixes) && prefixes.includes(prefix))?.[0] || '';
            };

            const updateDetectedOperator = () => {
                numberInput.value = numberInput.value.replace(/[^0-9]/g, '').slice(0, 11);

                const detectedKey = detectOperator(numberInput.value);

                if (!detectedKey) {
                    detectedOperatorText.textContent = '';
                    detectedOperatorText.classList.add('hidden');
                    return;
                }

                const matchingOption = Array.from(operatorSelect.options).find((option) => option.value && option.value.toLowerCase().replace(/[^a-z]/g, '') === detectedKey);

                if (matchingOption) {
                    operatorSelect.value = matchingOption.value;
                }

                detectedOperatorText.textContent = `Auto detected from number: ${optionLabels[detectedKey] || matchingOption?.textContent?.trim() || detectedKey}`;
                detectedOperatorText.classList.remove('hidden');
            };

            numberInput.addEventListener('input', updateDetectedOperator);
            updateDetectedOperator();
        })();
    </script>
</body>

</html>