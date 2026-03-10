<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Balance Report' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
    <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="min-h-screen bg-base-200">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </label>
                </div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Balance Report</a></div>
            </div>

            <div class="p-6 space-y-6">
                <div>
                    <h1 class="text-3xl font-bold">Balance Report</h1>
                    <p class="text-base-content/70 mt-1">My reseller balance summary, SIM balance snapshot, and current balance status.</p>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <div class="card bg-base-100 shadow-lg border border-base-200">
                        <div class="card-body space-y-4">
                            <div>
                                <h2 class="text-2xl font-bold">My Resellers balance</h2>
                                <p class="text-sm text-base-content/60">All reseller jader balance 00 theke beshi and total reseller balance.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="rounded-2xl bg-base-200 px-5 py-5">
                                    <div class="text-sm font-medium text-base-content/60">All Reseller ( Balance &gt; 0 )</div>
                                    <div class="mt-2 text-3xl font-extrabold text-primary">{{ $resellerPositiveCount }}</div>
                                </div>
                                <div class="rounded-2xl bg-base-200 px-5 py-5">
                                    <div class="text-sm font-medium text-base-content/60">Total</div>
                                    <div class="mt-2 text-3xl font-extrabold text-primary">{{ number_format((float) $resellerBalanceTotal, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow-lg border border-base-200">
                        <div class="card-body space-y-4">
                            <div>
                                <h2 class="text-2xl font-bold">Balance Status</h2>
                                <p class="text-sm text-base-content/60">SIM Balance Total - My Reseller Balance Total</p>
                            </div>
                            <div class="space-y-3">
                                <div class="rounded-2xl bg-base-200 px-5 py-4 flex items-center justify-between gap-4">
                                    <span class="font-semibold">My Reseller Balance</span>
                                    <span class="text-lg font-bold">{{ number_format((float) $resellerBalanceTotal, 2) }}</span>
                                </div>
                                <div class="rounded-2xl bg-base-200 px-5 py-4 flex items-center justify-between gap-4">
                                    <span class="font-semibold">SIM Balance</span>
                                    <span class="text-lg font-bold">{{ number_format((float) $simBalanceTotal, 2) }}</span>
                                </div>
                                <div class="rounded-2xl px-5 py-5 flex items-center justify-between gap-4 {{ $balanceStatus >= 0 ? 'bg-success/10 border border-success/20' : 'bg-error/10 border border-error/20' }}">
                                    <span class="text-lg font-bold">Balance Status</span>
                                    <span class="text-3xl font-extrabold {{ $balanceStatus >= 0 ? 'text-success' : 'text-error' }}">{{ number_format((float) $balanceStatus, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-lg border border-base-200">
                    <div class="card-body p-0">
                        <div class="px-6 pt-6">
                            <h2 class="text-2xl font-bold">SIM Balance</h2>
                        </div>
                        <div class="overflow-x-auto p-6 pt-4">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Nr.</th>
                                        <th>Operator</th>
                                        <th class="text-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($simBalanceRows as $row)
                                    <tr>
                                        <td>{{ $row['nr'] }}</td>
                                        <td>{{ $row['operator'] }}</td>
                                        <td class="text-right font-medium">{{ number_format((float) $row['balance'], 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-base-content/60 py-8">No SIM balance snapshot found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-right">Total</th>
                                        <th class="text-right text-primary">{{ number_format((float) $simBalanceTotal, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
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
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg></div>
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
                                <li><a href="{{ route('admin.balance.report') }}" class="active bg-primary text-primary-content">Balance Reports</a></li>
                                <li><a href="{{ route('admin.operator.reports') }}">Operator Reports</a></li>
                                <li><a href="{{ route('admin.daily.reports') }}">Daily Reports</a></li>
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