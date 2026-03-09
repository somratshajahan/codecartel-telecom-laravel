<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Buy Internet Package' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
                    <span class="font-semibold hidden sm:inline">{{ Auth::user()->name }}</span>
                </div>
            </div>

            <div class="container mx-auto p-6 flex-1 flex items-center justify-center">
                <div class="card bg-base-100 shadow-xl w-full max-w-md">
                    <div class="card-body">
                        <h2 class="card-title text-2xl mb-4">Buy Internet Package</h2>

                        <div class="bg-base-200 p-4 rounded-lg mb-6">
                            <h3 class="font-bold text-lg mb-2">{{ $package->name }}</h3>
                            <div class="flex justify-between mb-1"><span>Operator:</span><span class="font-bold">{{ $operator }}</span></div>
                            <div class="flex justify-between mb-1"><span>Price:</span><span class="font-bold">৳{{ $package->price }}</span></div>
                            <div class="flex justify-between mb-1"><span>Commission:</span><span class="font-bold text-success">৳{{ $package->commission }}</span></div>
                            <div class="divider my-2"></div>
                            <div class="flex justify-between"><span class="font-bold">You Pay:</span><span class="font-bold text-primary text-xl">৳{{ $package->price - $package->commission }}</span></div>
                        </div>

                        <form id="purchaseForm">
                            <div class="form-control mb-4">
                                <label class="label justify-center"><span class="label-text font-semibold">Mobile Number</span></label>
                                <input type="text" id="mobile" placeholder="01XXXXXXXXX" class="input input-bordered" maxlength="11" inputmode="numeric" pattern="[0-9]*" autocomplete="off" required />
                                <label class="label"><span class="label-text-alt text-error" id="mobileError"></span></label>
                            </div>
                            <div class="form-control mb-6">
                                <label class="label justify-center"><span class="label-text font-semibold">PIN</span></label>
                                <input type="password" id="pin" placeholder="Enter 4-digit PIN" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" class="input input-bordered" required />
                            </div>

                            <a href="#" id="confirmLink" class="btn btn-primary w-full">Confirm</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const operatorPrefixes = {
            grameenphone: ['017', '013'],
            gp: ['017', '013'],
            robi: ['018'],
            airtel: ['016'],
            banglalink: ['019', '014'],
            bl: ['019', '014'],
            teletalk: ['015'],
            tt: ['015']
        };

        const currentOperator = '{{ $operator }}'.toLowerCase().replace(/[^a-z]/g, '');
        const validPrefixes = operatorPrefixes[currentOperator] || [];
        const mobileInput = document.getElementById('mobile');
        const pinInput = document.getElementById('pin');
        const mobileError = document.getElementById('mobileError');

        function validateMobile() {
            const mobile = mobileInput.value;

            if (!mobile) {
                mobileError.textContent = '';
                return false;
            }

            if (mobile.length !== 11) {
                mobileError.textContent = 'Mobile number must be exactly 11 digits';
                return false;
            }

            const prefix = mobile.substring(0, 3);
            if (validPrefixes.length && !validPrefixes.includes(prefix)) {
                mobileError.textContent = 'Invalid number for {{ $operator }}. Must start with: ' + validPrefixes.join(', ');
                return false;
            }

            mobileError.textContent = '';
            return true;
        }

        mobileInput.addEventListener('input', function() {
            const originalValue = this.value;
            const sanitizedValue = originalValue.replace(/[^0-9]/g, '');
            const hadInvalidCharacters = originalValue !== sanitizedValue;

            this.value = sanitizedValue;

            if (hadInvalidCharacters) {
                mobileError.textContent = 'Only numbers are allowed in mobile number';
                return;
            }

            validateMobile();
        });

        pinInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('confirmLink').addEventListener('click', function(e) {
            e.preventDefault();

            const mobile = mobileInput.value;
            const pin = pinInput.value;

            if (!validateMobile()) {
                return;
            }

            if (!pin || pin.length !== 4) {
                alert('Please enter valid 4-digit PIN');
                return;
            }

            const target = `{{ route('user.internet.confirm', ['operator' => $operator, 'package' => $package->id]) }}?mobile=${mobile}&pin=${pin}`;
            window.location.href = target;
        });
    </script>
</body>

</html>