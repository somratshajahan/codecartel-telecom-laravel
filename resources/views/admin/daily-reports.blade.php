<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Daily Reports' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Daily Reports</a></div>
            </div>

            <div class="p-6 space-y-6">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">Daily Reports</h1>
                        <p class="text-base-content/70 mt-1">Daily summary for the selected date.</p>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-lg border border-base-200">
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.daily.reports') }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4 items-end">
                            <div>
                                <label class="label py-1"><span class="label-text font-medium">Date</span></label>
                                <input type="date" name="date" value="{{ $selectedDate }}" class="input input-bordered w-full" />
                            </div>
                            <div><button type="submit" class="btn btn-primary w-full md:w-auto">Filter</button></div>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($reportCards as $card)
                    <div class="card bg-base-100 shadow-lg border border-base-200">
                        <div class="card-body">
                            <h2 class="text-xl font-bold">{{ $card['title'] }}</h2>
                            <p class="text-sm text-base-content/60">Selected Date: {{ $selectedDate }}</p>
                            <div class="mt-4 rounded-2xl bg-base-200 px-4 py-5">
                                <p class="text-sm text-base-content/60">Total</p>
                                <div class="mt-1 text-3xl font-extrabold text-primary">{{ number_format((float) $card['total'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="drawer-side z-40">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 min-h-screen border-r border-base-200">
                <div class="p-4 border-b border-base-200">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        @if(optional($settings)->company_logo_url)
                        <img src="{{ asset(optional($settings)->company_logo_url) }}" alt="Logo" class="h-10 w-10 object-contain rounded-lg">
                        @else
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        @endif
                        <span class="text-lg font-bold sidebar-text">{{ optional($settings)->company_name ?? 'Codecartel' }}</span>
                    </a>
                </div>

                <ul class="menu p-4 gap-1">
                    <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li>
                        <details open>
                            <summary>Reports</summary>
                            <ul>
                                <li><a>Receive reports</a></li>
                                <li><a href="{{ route('admin.balance.report') }}">Balance Reports</a></li>
                                <li><a href="{{ route('admin.operator.reports') }}">Operator Reports</a></li>
                                <li><a href="{{ route('admin.daily.reports') }}" class="active bg-primary text-primary-content">Daily Reports</a></li>
                                <li><a>Total usages</a></li>
                                <li><a>Transaction</a></li>
                                <li><a>Trnx ID</a></li>
                                <li><a href="{{ route('admin.sales.report') }}">Sales Report</a></li>
                            </ul>
                        </details>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</body>

</html>