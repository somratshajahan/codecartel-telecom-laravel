<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Add Balance' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold">Add Balance</h1>
                <p class="text-base-content/70">Manual payment number দেখে payment করে নিচের form-এ transaction info submit করুন।</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost">← Back to Dashboard</a>
        </div>

        @if(session('success'))
        <div class="alert alert-success mb-6">
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-warning mb-6">
            <span>{{ session('error') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-error mb-6">
            <div>
                <div class="font-semibold">Please fix the following issues:</div>
                <ul class="list-disc pl-5 text-sm mt-2">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <div class="grid gap-4 md:grid-cols-3 mb-8">
            <div class="stat bg-base-100 rounded-2xl shadow">
                <div class="stat-title">Main Balance</div>
                <div class="stat-value text-primary">৳{{ number_format($user->main_bal ?? 0, 2) }}</div>
            </div>
            <div class="stat bg-base-100 rounded-2xl shadow">
                <div class="stat-title">Drive Balance</div>
                <div class="stat-value text-secondary">৳{{ number_format($user->drive_bal ?? 0, 2) }}</div>
            </div>
            <div class="stat bg-base-100 rounded-2xl shadow">
                <div class="stat-title">Bank Balance</div>
                <div class="stat-value text-accent">৳{{ number_format($user->bank_bal ?? 0, 2) }}</div>
            </div>
        </div>

        @if($manualMethods->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
            @foreach($manualMethods as $method)
            <div class="card bg-base-100 shadow-xl border border-base-300">
                <div class="card-body text-center">
                    <div class="badge {{ $method['color'] }} text-white mx-auto">{{ $method['name'] }}</div>
                    <h2 class="text-2xl font-bold mt-3">{{ $method['number'] }}</h2>
                    <p class="text-sm text-base-content/60">এই নাম্বারে cash in/send money করুন</p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="alert alert-warning mb-8">
            <span>এখনও কোনো payment method configure করা হয়নি। অনুগ্রহ করে admin-এর সাথে যোগাযোগ করুন।</span>
        </div>
        @endif

        @if($manualMethods->isNotEmpty())
        <div class="card bg-base-100 shadow-xl mb-8">
            <div class="card-body">
                <h2 class="card-title">Submit Payment Request</h2>
                <form method="POST" action="{{ route('user.add.balance.submit') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <div class="form-control">
                        <label class="label"><span class="label-text">Method</span></label>
                        <select name="method" class="select select-bordered" required>
                            <option value="">Select method</option>
                            @foreach($manualMethods as $method)
                            <option value="{{ $method['name'] }}" {{ old('method') === $method['name'] ? 'selected' : '' }}>{{ $method['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Sender Number</span></label>
                        <input type="text" name="sender_number" value="{{ old('sender_number') }}" class="input input-bordered" placeholder="01XXXXXXXXX" required />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Transaction ID</span></label>
                        <input type="text" name="transaction_id" value="{{ old('transaction_id') }}" class="input input-bordered" placeholder="Enter transaction ID" required />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Amount</span></label>
                        <input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" class="input input-bordered" placeholder="Enter amount" required />
                    </div>
                    <div class="form-control md:col-span-2">
                        <label class="label"><span class="label-text">Note</span></label>
                        <textarea name="note" class="textarea textarea-bordered" placeholder="Optional note for admin">{{ old('note') }}</textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">How to add balance</h2>
                    <ol class="list-decimal pl-5 space-y-2 text-sm text-base-content/80">
                        <li>উপরের যেকোনো payment number-এ টাকা পাঠান।</li>
                        <li>Transaction ID, amount, এবং method note করে রাখুন।</li>
                        <li>নিচের form-এ details submit করুন, তারপর admin approval-এর পর balance update হবে।</li>
                    </ol>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Support</h2>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-semibold">Phone:</span> {{ optional($branding)->alert_no ?: 'Not configured' }}</p>
                        <p><span class="font-semibold">WhatsApp:</span> {{ optional($branding)->whatsapp_link ?: 'Not configured' }}</p>
                        <p class="text-base-content/60">Payment করার পর support-এ যোগাযোগ করলে balance add করা সহজ হবে।</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl mt-8">
            <div class="card-body">
                <h2 class="card-title">Recent Requests</h2>
                @if(($recentRequests ?? collect())->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Sender</th>
                                <th>Transaction ID</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Admin Note</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(($recentRequests ?? collect()) as $request)
                            @php
                            $status = strtolower((string) $request->status);
                            @endphp
                            <tr>
                                <td><span class="badge badge-primary">{{ $request->method }}</span></td>
                                <td>{{ $request->sender_number }}</td>
                                <td>{{ $request->transaction_id }}</td>
                                <td>৳{{ number_format($request->amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $status === 'approved' ? 'badge-success' : ($status === 'rejected' ? 'badge-error' : 'badge-warning') }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td>{{ $request->admin_note ?: '-' }}</td>
                                <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-sm text-base-content/60">No manual payment request found yet.</p>
                @endif
            </div>
        </div>
    </div>
</body>

</html>