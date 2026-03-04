<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pending Requests - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-base-200">
    <div class="p-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">My Pending Drive Requests</h1>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost">Back to Dashboard</a>
        </div>
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                @if($pendingRequests->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>Operator</th>
                                    <th>Package</th>
                                    <th>Mobile</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingRequests as $req)
                                    <tr>
                                        <td><span class="badge badge-primary">{{ $req->operator }}</span></td>
                                        <td>{{ $req->package->name ?? 'N/A' }}</td>
                                        <td>{{ $req->mobile }}</td>
                                        <td>৳{{ number_format($req->amount, 2) }}</td>
                                        <td><span class="badge badge-warning">Pending</span></td>
                                        <td>{{ $req->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-base-content/60 text-center">No pending requests</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
