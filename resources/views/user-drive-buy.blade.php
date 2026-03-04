<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Buy Package' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; }</style>
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
                            <li><a>Profile</a></li>
                            <li><a>Settings</a></li>
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

            <div class="container mx-auto p-6 flex-1 flex items-center justify-center">
                <div class="card bg-base-100 shadow-xl w-full max-w-md">
                    <div class="card-body">
                        <h2 class="card-title text-2xl mb-4">Buy Drive Package</h2>
                        
                        <div class="bg-base-200 p-4 rounded-lg mb-6">
                            <h3 class="font-bold text-lg mb-2">{{ $package->name }}</h3>
                            <div class="flex justify-between mb-1">
                                <span>Operator:</span>
                                <span class="font-bold">{{ $operator }}</span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span>Price:</span>
                                <span class="font-bold">৳{{ $package->price }}</span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span>Commission:</span>
                                <span class="font-bold text-success">৳{{ $package->commission }}</span>
                            </div>
                            <div class="divider my-2"></div>
                            <div class="flex justify-between">
                                <span class="font-bold">You Pay:</span>
                                <span class="font-bold text-primary text-xl">৳{{ $package->price - $package->commission }}</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('user.drive.purchase', ['operator' => $operator, 'package' => $package->id]) }}" id="purchaseForm">
                            @csrf
                            <div class="form-control mb-4">
                                <label class="label justify-center">
                                    <span class="label-text font-semibold">Mobile Number</span>
                                </label>
                                <label class="input input-bordered flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    <input type="text" name="mobile" id="mobile" placeholder="01XXXXXXXXX" class="grow" maxlength="11" inputmode="numeric" pattern="[0-9]*" autocomplete="off" required />
                                </label>
                                <label class="label">
                                    <span class="label-text-alt text-error" id="mobileError"></span>
                                </label>
                                <div id="recentNumbers" class="mt-2 space-y-1 hidden"></div>
                            </div>

                            <div class="form-control mb-6">
                                <label class="label justify-center">
                                    <span class="label-text font-semibold">PIN</span>
                                </label>
                                <label class="input input-bordered flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    <input type="password" name="pin" placeholder="Enter 4-digit PIN" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" class="grow" required />
                                </label>
                            </div>

                            <a href="{{ route('user.drive.confirm', ['operator' => $operator, 'package' => $package->id]) }}?mobile=" id="confirmLink" class="btn btn-primary w-full">Confirm</a>
                        </form>

                        <script>
                            const operatorPrefixes = {
                                'GrameenPhone': ['017', '013'],
                                'Robi': ['018'],
                                'Airtel': ['016'],
                                'Banglalink': ['019', '014'],
                                'Teletalk': ['015']
                            };
                            const currentOperator = '{{ $operator }}';
                            const validPrefixes = operatorPrefixes[currentOperator] || [];

                            const form = document.getElementById('purchaseForm');
                            const confirmBtn = document.getElementById('confirmBtn');
                            const mobileInput = document.getElementById('mobile');
                            const recentNumbersDiv = document.getElementById('recentNumbers');
                            let holdTimer;
                            let progress = 0;

                            // Load recent numbers from localStorage
                            function getRecentNumbers() {
                                const recent = localStorage.getItem('recentDriveNumbers');
                                return recent ? JSON.parse(recent) : [];
                            }

                            function saveRecentNumber(number) {
                                let recent = getRecentNumbers();
                                recent = recent.filter(n => n !== number);
                                recent.unshift(number);
                                recent = recent.slice(0, 5);
                                localStorage.setItem('recentDriveNumbers', JSON.stringify(recent));
                            }

                            function showRecentNumbers() {
                                const recent = getRecentNumbers();
                                if (recent.length > 0) {
                                    recentNumbersDiv.innerHTML = '<div class="text-xs font-semibold mb-1">Recent Numbers:</div>';
                                    recent.forEach(number => {
                                        const btn = document.createElement('button');
                                        btn.type = 'button';
                                        btn.className = 'btn btn-sm btn-outline w-full justify-start';
                                        btn.textContent = number;
                                        btn.onclick = () => {
                                            mobileInput.value = number;
                                            recentNumbersDiv.classList.add('hidden');
                                        };
                                        recentNumbersDiv.appendChild(btn);
                                    });
                                    recentNumbersDiv.classList.remove('hidden');
                                }
                            }

                            mobileInput.addEventListener('focus', showRecentNumbers);
                            mobileInput.addEventListener('blur', () => {
                                setTimeout(() => recentNumbersDiv.classList.add('hidden'), 200);
                            });

                            function validateForm() {
                                const mobile = mobileInput.value;
                                const error = document.getElementById('mobileError');
                                
                                if (mobile.length !== 11) {
                                    error.textContent = 'Mobile number must be exactly 11 digits';
                                    return false;
                                }
                                
                                const prefix = mobile.substring(0, 3);
                                if (!validPrefixes.includes(prefix)) {
                                    error.textContent = 'Invalid number for ' + currentOperator + '. Must start with: ' + validPrefixes.join(', ');
                                    return false;
                                }
                                
                                error.textContent = '';
                                return true;
                            }

                            function submitForm() {
                                saveRecentNumber(mobileInput.value);
                                form.submit();
                            }

                            const confirmLink = document.getElementById('confirmLink');

                            confirmLink.addEventListener('click', function(e) {
                                if (!validateForm()) {
                                    e.preventDefault();
                                    return;
                                }
                                const mobile = mobileInput.value;
                                const pin = document.querySelector('input[name="pin"]').value;
                                if (!pin || pin.length !== 4) {
                                    e.preventDefault();
                                    alert('Please enter a valid 4-digit PIN');
                                    return;
                                }
                                saveRecentNumber(mobile);
                                this.href = `{{ route('user.drive.confirm', ['operator' => $operator, 'package' => $package->id]) }}?mobile=${mobile}&pin=${pin}`;
                            });

                            mobileInput.addEventListener('input', function(e) {
                                this.value = this.value.replace(/[^0-9]/g, '');
                                document.getElementById('mobileError').textContent = '';
                            });
                        </script>
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
                <li><a href="{{ route('dashboard') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>Dashboard</a></li>
                <li><details><summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>New Request</span></summary><ul class="p-2"><li><a href="#">Flexiload</a></li><li><a href="#">Internet Pack</a></li><li><a href="{{ route('user.drive') }}">Drive</a></li><li><a href="#">Bkash</a></li><li><a href="#">Nagad</a></li><li><a href="#">Rocket</a></li><li><a href="#">Upay</a></li><li><a href="#">Islami Bank</a></li><li><a href="#">Bulk Flexi</a></li></ul></details></li>
                <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Pending Request</a></li>
                <li><details><summary><span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5M21 21v-5h-5M4 4l16 16" /></svg>History</span></summary><ul class="p-2"><li><a href="#">All history</a></li><li><a href="#">Flexiload</a></li><li><a href="#">Internet Pack</a></li><li><a href="#">Drive</a></li><li><a href="#">Bkash</a></li><li><a href="#">Nagad</a></li><li><a href="#">Rocket</a></li><li><a href="#">Upay</a></li><li><a href="#">Islami Bank</a></li></ul></details></li>
                <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex items-center gap-2 w-full"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>Logout</button></form></li>
            </ul>
        </div>
    </div>
</body>
</html>
