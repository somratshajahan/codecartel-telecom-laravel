<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My History - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
    <div class="p-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">My History</h1>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost">Back to Dashboard</a>
        </div>
        <div class="card bg-base-100 shadow-md mb-6">
            <div class="card-body gap-4">
                <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">History Filter</h2>
                        <p class="text-sm text-base-content/70">Defaultভাবে আজকের history দেখাচ্ছে। পুরনো data দেখতে date filter use করুন।</p>
                    </div>
                    <div class="badge badge-info badge-outline">Auto updates after 12:00 AM</div>
                </div>
                <form method="GET" action="{{ route('user.all.history') }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Date From</span></label>
                            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="input input-bordered" />
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Date To</span></label>
                            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="input input-bordered" />
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('user.all.history') }}" class="btn btn-ghost">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Operator</th>
                                <th>Mobile</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $item)
                            <tr>
                                <td>
                                    @if(($item->type ?? 'drive') === 'drive')
                                    <span class="badge badge-info">Drive</span>
                                    @elseif(($item->type ?? '') === 'flexi')
                                    <span class="badge badge-secondary">Flexi</span>
                                    @else
                                    <span class="badge badge-primary">Internet</span>
                                    @endif
                                </td>
                                <td><span class="badge badge-primary">{{ $item->operator }}</span></td>
                                <td>{{ $item->mobile }}</td>
                                <td>৳{{ number_format($item->amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $item->status == 'success' ? 'badge-success' : (($item->status ?? '') == 'cancelled' ? 'badge-warning' : 'badge-error') }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td>{{ $item->description ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No history found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>