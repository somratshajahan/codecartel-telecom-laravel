<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Transfer</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (() => {
            try {
                const theme = window.localStorage.getItem('cc_theme');
                if (theme === 'dark' || theme === 'light') {
                    document.documentElement.setAttribute('data-theme', theme);
                    document.documentElement.style.colorScheme = theme;
                }
            } catch (error) {
                // Ignore storage issues; app.js will apply fallback theme.
            }
        })();
    </script>
    <style>
        html,
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-base-200 text-base-content transition-colors duration-300">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none lg:hidden">
                    <label for="my-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a href="{{ route('dashboard') }}" class="px-2 text-xl font-bold">
                        {{ optional(App\Models\HomepageSetting::first())->company_name ?? 'Codecartel Telecom' }}
                    </a>
                </div>
                <div class="flex-none flex items-center gap-2">
                    @include('partials.theme-toggle')
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="font-bold">৳ {{ number_format(Auth::user()->main_bal ?? 0, 2) }}</span>
                        </div>
                        <ul tabindex="0" class="mt-3 z-1 p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            <li class="menu-title">Balance Details</li>
                            <li><a>Main: ৳ {{ number_format(Auth::user()->main_bal ?? 0, 2) }}</a></li>
                            <li><a>Drive: ৳ {{ number_format(Auth::user()->drive_bal ?? 0, 2) }}</a></li>
                            <li><a>Bank: ৳ {{ number_format(Auth::user()->bank_bal ?? 0, 2) }}</a></li>
                        </ul>
                    </div>
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center overflow-hidden">
                                <span class="font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </div>
                        </div>
                        <ul tabindex="0" class="mt-3 z-1 p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            <li><a href="{{ route('user.profile') }}">Profile</a></li>
                            <li><a href="{{ route('user.profile.google-otp') }}">Google OTP</a></li>
                            <li><a href="{{ route('user.profile.api') }}">API</a></li>
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

            <!-- Main Content -->
            <div class="p-6 bg-base-200">
                <div class="bg-base-100 p-8 rounded-lg shadow-lg max-w-2xl mx-auto">
                    <h2 class="text-2xl font-bold mb-6">Balance Transfer</h2>
                    
                    @if(session('success'))
                        <div class="alert alert-success mb-4">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="alert alert-error mb-4">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form id="balanceTransferForm" action="{{ route('balance.transfer.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="receiver_username" class="block font-medium mb-2">Receiver Username</label>
                            <input type="text" name="receiver_username" id="receiver_username" class="input input-bordered w-full" placeholder="Enter receiver username" list="receiverSuggestions" autocomplete="off" required>
                            <datalist id="receiverSuggestions"></datalist>
                        </div>

                        <div class="mb-4">
                            <label for="account_type" class="block font-medium mb-2">Transfer From Account</label>
                            <select name="transfer_type" id="account_type" class="select select-bordered w-full" required>
                                <option value="main">Main Balance</option>
                                <option value="bank">Bank Balance</option>
                                <option value="drive">Drive Balance</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="available_balance" class="block font-medium mb-2">Available Balance</label>
                            <input type="text" name="available_balance" id="available_balance" class="input input-bordered w-full" readonly value="0.00">
                        </div>

                        <div class="mb-4">
                            <label for="amount" class="block font-medium mb-2">Amount to Transfer</label>
                            <input type="number" name="amount" id="amount" class="input input-bordered w-full" placeholder="Enter amount" min="0.01" step="0.01" required>
                        </div>

                        <div class="mb-4">
                            <label for="pin" class="block font-medium mb-2">Security PIN</label>
                            <input type="password" name="pin" id="pin" class="input input-bordered w-full" placeholder="Enter 4-digit PIN" maxlength="4" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Transfer Balance</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar/Aside Menu -->
        <div class="drawer-side">
            <label for="my-drawer" class="drawer-overlay"></label>
            <ul class="menu p-4 w-60 min-h-full bg-base-100 text-base-content border-r border-base-300">
                <li>
                    <a href="{{ route('dashboard') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('balance.transfer.index') }}" class="active bg-primary text-primary-content">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        Balance Transfer
                    </a>
                </li>
                <li>
                    <details>
                        <summary>
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Prepaid Card
                            </span>
                        </summary>
                        <ul class="p-2">
                            <li><a href="{{ route('user.flexi') }}">Flexiload</a></li>
                            <li><a href="{{ route('user.internet') }}">Internet Pack</a></li>
                            <li><a href="{{ route('user.drive') }}">Drive</a></li>
                            <li><a href="{{ route('user.add.balance') }}">Add Balance</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <a href="{{ route('user.all.history') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5M21 21v-5h-5M4 4l16 16" />
                        </svg>
                        History
                    </a>
                </li>
                <li>
                    <a href="{{ route('user.profile') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Profile
                    </a>
                </li>
                <li>
                    <a href="{{ route('complaints.index') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Complaint
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Update available balance based on account type
        document.addEventListener('DOMContentLoaded', function () {
            const accountTypeSelect = document.getElementById('account_type');
            const availableBalanceInput = document.getElementById('available_balance');
            const amountInput = document.getElementById('amount');
            const receiverUsernameInput = document.getElementById('receiver_username');
            const receiverSuggestions = document.getElementById('receiverSuggestions');
            let usernameSearchDebounce = null;

            const normalizeReceiverUsername = (value) => {
                if (!value) return '';

                return value
                    .replace(/[\u200B\u200C\u200D\uFEFF]/g, '')
                    .replace(/\s*\(.*\)\s*$/, '')
                    .replace(/^@+/, '')
                    .trim();
            };

            const loadBalanceByType = (accountType) => {
                if (!accountType) {
                    return;
                }

                fetch(`{{ route("balance.transfer.checkBalance") }}?type=${accountType}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const balance = Number(data.balance || 0);
                            availableBalanceInput.value = balance.toFixed(2);
                            amountInput.max = balance;
                            amountInput.placeholder = `Enter amount (Max: ${balance.toFixed(2)})`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            };

            const loadUserSuggestions = (query) => {
                fetch(`{{ route("balance.transfer.userSuggestions") }}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        receiverSuggestions.innerHTML = '';

                        if (!data.success || !Array.isArray(data.users)) {
                            return;
                        }

                        data.users.forEach((user) => {
                            const option = document.createElement('option');
                            option.value = user.username;
                            option.label = `${user.username}${user.name ? ` (${user.name})` : ''}${user.mobile ? ` • ${user.mobile}` : ''}`;
                            receiverSuggestions.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Suggestion error:', error);
                    });
            };
            
            // Update balance when account type changes
            accountTypeSelect.addEventListener('change', function () {
                loadBalanceByType(this.value);
            });

            receiverUsernameInput.addEventListener('input', function () {
                const query = this.value.trim();

                clearTimeout(usernameSearchDebounce);

                if (query.length < 1) {
                    receiverSuggestions.innerHTML = '';
                    return;
                }

                usernameSearchDebounce = setTimeout(() => {
                    loadUserSuggestions(query);
                }, 250);
            });

            receiverUsernameInput.addEventListener('blur', function () {
                this.value = normalizeReceiverUsername(this.value);
            });

            // Form submission handler
            document.getElementById('balanceTransferForm').addEventListener('submit', function(e) {
                e.preventDefault();

                receiverUsernameInput.value = normalizeReceiverUsername(receiverUsernameInput.value);
                
                // Validate amount against available balance
                const amount = parseFloat(amountInput.value);
                const availableBalance = parseFloat(availableBalanceInput.value);
                
                if(isNaN(amount) || amount <= 0) {
                    alert('Please enter a valid amount greater than zero');
                    return;
                }
                
                if(amount > availableBalance) {
                    alert(`Insufficient balance. Your available balance is ৳${availableBalance.toFixed(2)}`);
                    return;
                }
                
                // Submit form if validation passes
                this.submit();
            });
            
            // Initialize balance on page load (default: main)
            loadBalanceByType(accountTypeSelect.value || 'main');
        });
    </script>
</body>
</html>