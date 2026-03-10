<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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

@php
$columns = [
['key' => 'bkash_main', 'label' => 'bKash(Main)'],
['key' => 'rocket_main', 'label' => 'Rocket(Main)'],
['key' => 'nagad_main', 'label' => 'NAGAD(Main)'],
['key' => 'upay_main', 'label' => 'Upay(Main)'],
['key' => 'account_price', 'label' => 'Account Price'],
['key' => 'self_account_price', 'label' => 'Self Account Price'],
['key' => 'bkash_bank', 'label' => 'bKash(Bank)'],
['key' => 'rocket_bank', 'label' => 'Rocket(Bank)'],
['key' => 'nagad_bank', 'label' => 'NAGAD(Bank)'],
['key' => 'upay_bank', 'label' => 'Upay(Bank)'],
['key' => 'bkash_drive', 'label' => 'bKash(Drive)'],
['key' => 'rocket_drive', 'label' => 'Rocket(Drive)'],
['key' => 'nagad_drive', 'label' => 'NAGAD(Drive)'],
['key' => 'upay_drive', 'label' => 'Upay(Drive)'],
];

$firstRow = $depositLevels[0] ?? null;
@endphp

<body class="min-h-screen bg-base-200 text-base-content">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none"><label for="my-drawer" class="btn btn-square btn-ghost"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg></label></div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Deposit</a></div>
            </div>

            <div class="p-6">
                <div class="max-w-7xl mx-auto space-y-6">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body space-y-6">
                            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <h2 class="text-3xl font-bold">🏦 Deposit Settings</h2>
                                    <p class="text-sm opacity-70">Level-wise amount edit করে save করতে পারবেন। Main method percent অনুযায়ী add balance approval-এ extra credit apply হবে।</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="badge badge-primary badge-lg">{{ count($depositLevels) }} Levels</span>
                                    <span class="badge badge-outline badge-lg">4 Main</span>
                                    <span class="badge badge-outline badge-lg">4 Bank</span>
                                    <span class="badge badge-outline badge-lg">4 Drive</span>
                                </div>
                            </div>

                            @if(session('success'))
                            <div class="alert alert-success text-sm"><span>{{ session('success') }}</span></div>
                            @endif

                            @if(session('warning'))
                            <div class="alert alert-warning text-sm"><span>{{ session('warning') }}</span></div>
                            @endif

                            @if($errors->any())
                            <div class="alert alert-error text-sm">
                                <div>
                                    <p class="font-semibold">Please fix the highlighted deposit fields.</p>
                                    <ul class="list-disc ml-4">
                                        @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            @endif

                            @if(! $depositSettingsTableExists)
                            <div class="alert alert-warning text-sm">
                                <span>Deposit settings table এখনো DB-তে নেই। Latest migration run করার পর save fully কাজ করবে, তবে default values এখন show হচ্ছে।</span>
                            </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="card bg-base-200 shadow-sm">
                                    <div class="card-body p-5">
                                        <p class="text-sm opacity-70">Main Method Rate</p>
                                        <p class="text-2xl font-bold">{{ $firstRow['bkash_main'] ?? 0 }}%</p>
                                        <p class="text-xs opacity-70">Default bKash main bonus rate</p>
                                    </div>
                                </div>
                                <div class="card bg-base-200 shadow-sm">
                                    <div class="card-body p-5">
                                        <p class="text-sm opacity-70">Admin Account Price</p>
                                        <p class="text-2xl font-bold">{{ $firstRow['account_price'] ?? 0 }}</p>
                                        <p class="text-xs opacity-70">Admin created account starts with negative main balance</p>
                                    </div>
                                </div>
                                <div class="card bg-base-200 shadow-sm">
                                    <div class="card-body p-5">
                                        <p class="text-sm opacity-70">Self Account Price</p>
                                        <p class="text-2xl font-bold">{{ $firstRow['self_account_price'] ?? 0 }}</p>
                                        <p class="text-xs opacity-70">Registration starts with negative main balance by level</p>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.deposit.update') }}" class="space-y-5">
                                @csrf

                                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                    <p class="text-sm opacity-70">`Main` columns = bonus percent for manual add balance approval. `Account Price` / `Self Account Price` = opening negative main balance.</p>
                                    <button type="submit" class="btn btn-primary">Save Deposit Settings</button>
                                </div>

                                <div class="overflow-x-auto rounded-2xl border border-base-200">
                                    <table class="table table-zebra w-full min-w-[1700px]">
                                        <thead class="bg-base-200 text-base-content">
                                            <tr>
                                                <th>Level</th>
                                                <th>Level Name</th>
                                                @foreach($columns as $column)
                                                <th class="text-center whitespace-nowrap">{{ $column['label'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($depositLevels as $row)
                                            <tr>
                                                <td><span class="badge badge-primary badge-outline">{{ $row['level'] }}</span></td>
                                                <td class="font-semibold whitespace-nowrap">{{ $row['level_name'] }}</td>
                                                @foreach($columns as $column)
                                                @php($fieldName = 'deposit_levels.' . $row['level'] . '.' . $column['key'])
                                                <td class="text-center">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        name="deposit_levels[{{ $row['level'] }}][{{ $column['key'] }}]"
                                                        value="{{ old($fieldName, $row[$column['key']]) }}"
                                                        class="input input-bordered input-sm w-24 text-center @error($fieldName) input-error @enderror" />
                                                </td>
                                                @endforeach
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="btn btn-primary">Save Deposit Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="drawer-side z-40">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 min-h-screen border-r border-base-200">
                <div class="p-4 border-b border-base-200"><a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2"><span class="text-lg font-bold sidebar-text">{{ optional($settings)->company_name ?? 'Codecartel' }}</span></a></div>
                <ul class="menu p-4 gap-1">
                    <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li>
                        <details open>
                            <summary><span class="sidebar-text">Administration</span></summary>
                            <ul>
                                <li><a href="{{ route('api.index') }}">Api Settings</a></li>
                                <li><a href="{{ route('admin.deposit') }}" class="active">Deposit</a></li>
                                <li><a href="{{ route('admin.payment.gateway') }}">Payment Gateway</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><span class="sidebar-text">Tools</span></summary>
                            <ul>
                                <li><a href="{{ route('admin.branding') }}">Branding</a></li>
                                <li><a href="{{ route('admin.device.logs') }}">Device Logs</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <details>
                            <summary><span class="sidebar-text">Settings</span></summary>
                            <ul>
                                <li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li>
                                <li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li>
                                <li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li>
                                <li><a href="{{ route('admin.firebase.config') }}">Firebase Credentials</a></li>
                            </ul>
                        </details>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">Logout</button></form>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</body>

</html>