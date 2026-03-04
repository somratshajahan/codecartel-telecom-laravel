<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Resellers - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-base-200">
    <div class="p-4">
        <h1 class="text-3xl font-bold mb-6">All Resellers</h1>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Level</th>
                                <th>Main Balance</th>
                                <th>Drive Balance</th>
                                <th>Bank Balance</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td><span class="badge badge-primary">{{ ucfirst($user->level ?? 'N/A') }}</span></td>
                                    <td>৳{{ number_format($user->main_bal ?? 0, 2) }}</td>
                                    <td>৳{{ number_format($user->drive_bal ?? 0, 2) }}</td>
                                    <td>৳{{ number_format($user->bank_bal ?? 0, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-error' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at->format('d M Y') }}</td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a href="/admin/add-balance/{{ $user->id }}" class="btn btn-primary btn-sm">Add Balance</a>
                                            <a href="/admin/return-balance/{{ $user->id }}" class="btn btn-error btn-sm">Return Balance</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
