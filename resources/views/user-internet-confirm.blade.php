<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Confirm Purchase' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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

            <div class="container mx-auto p-6 flex-1 flex items-center justify-center">
                <div class="card bg-base-100 shadow-xl w-full max-w-md">
                    <div class="card-body items-center text-center">
                        <h2 class="card-title text-2xl mb-4">Confirm Your Purchase</h2>

                        <div class="bg-base-200 p-4 rounded-lg mb-6 w-full">
                            <div class="flex justify-between mb-2">
                                <span>Package:</span>
                                <span class="font-bold">{{ $package->name }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>Mobile:</span>
                                <span class="font-bold">{{ $mobile }}</span>
                            </div>
                            <div class="divider my-2"></div>
                            <div class="flex justify-between">
                                <span class="font-bold">Amount:</span>
                                <span class="font-bold text-primary text-xl">&#2547;{{ $package->price - $package->commission }}</span>
                            </div>
                        </div>

                        <div id="holdSection" class="w-full">
                            <div id="warningSection" class="alert alert-warning mb-4 hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h3 class="font-bold">Purchase Failed!</h3>
                                    <div class="text-xs" id="warningText"></div>
                                </div>
                            </div>
                            <p class="text-sm mb-4">Tap and hold the button below to complete purchase</p>
                            <button id="holdBtn" class="btn btn-primary btn-lg w-full">
                                Tap and Hold to Confirm
                            </button>
                        </div>

                        <div id="successSection" class="hidden w-full">
                            <div class="text-success mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-success mb-2">Purchase Successful!</h3>
                            <p class="mb-4">Your internet package has been activated</p>
                            <a href="{{ route('user.internet') }}" class="btn btn-primary">Back to Internet Offers</a>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer items-center p-4 bg-base-300 text-base-content justify-center">
                <div class="items-center grid-flow-col">
                    <p>Copyright &copy; 2026 - All right reserved by {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</p>
                </div>
            </footer>
        </div>
    </div>

    @php
        $safeUserMainBalance = (float) (auth()->user()?->main_bal ?? 0);
        $safePackagePrice = (float) (((float) data_get($package, 'price', 0)) - ((float) data_get($package, 'commission', 0)));
    @endphp

    <script>
        const holdBtn = document.getElementById('holdBtn');
        const holdSection = document.getElementById('holdSection');
        const successSection = document.getElementById('successSection');
        const userMainBalance = @json($safeUserMainBalance);
        const packagePrice = @json($safePackagePrice);
        let holdTimer;
        let progress = 0;

        function startHold() {
            if (userMainBalance < packagePrice) {
                const warningSection = document.getElementById('warningSection');
                const warningText = document.getElementById('warningText');
                warningText.innerHTML = 'Required: &#2547;' + packagePrice.toFixed(2) + '<br>Available: &#2547;' + userMainBalance.toFixed(2) + '<br>Please add balance first.';
                warningSection.classList.remove('hidden');
                return;
            }

            progress = 0;
            holdBtn.style.background = 'linear-gradient(to right, #10b981 0%, #3b82f6 0%)';

            holdTimer = setInterval(function() {
                progress += 1;
                holdBtn.style.background = `linear-gradient(to right, #10b981 ${progress}%, #3b82f6 ${progress}%)`;

                if (progress >= 100) {
                    clearInterval(holdTimer);
                    completePurchase();
                }
            }, 20);
        }

        function stopHold() {
            clearInterval(holdTimer);
            holdBtn.style.background = '';
        }

        async function completePurchase() {
            const warningSection = document.getElementById('warningSection');
            const warningText = document.getElementById('warningText');

            warningSection.classList.add('hidden');
            holdBtn.disabled = true;
            holdBtn.classList.add('loading');

            try {
                const res = await fetch('{{ route("user.internet.purchase", ["operator" => $operator, "package" => $package->id]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mobile: '{{ $mobile }}',
                        pin: '{{ $pin }}'
                    })
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Purchase failed');
                }

                holdSection.classList.add('hidden');
                successSection.classList.remove('hidden');
            } catch (err) {
                warningText.innerHTML = err.message || 'Purchase failed. Please try again.';
                warningSection.classList.remove('hidden');
                holdBtn.disabled = false;
                holdBtn.classList.remove('loading');
                holdBtn.style.background = '';
            }
        }

        holdBtn.addEventListener('mousedown', startHold);
        holdBtn.addEventListener('mouseup', stopHold);
        holdBtn.addEventListener('mouseleave', stopHold);
        holdBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            startHold();
        });
        holdBtn.addEventListener('touchend', stopHold);
    </script>
</body>

</html>

