<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Balance - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
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

<body class="min-h-screen bg-base-200 flex items-center justify-center">
    <div class="container mx-auto p-6 max-w-2xl">
        <div class="card bg-base-100 shadow-2xl">
            <div class="card-body">
                <div class="flex flex-col gap-3 mb-6 md:flex-row md:items-center md:justify-between">
                    <h2 class="card-title text-3xl">Add Balance</h2>
                    <div class="flex gap-2">
                        <span class="btn btn-primary btn-sm pointer-events-none">Add Balance</span>
                        <a href="{{ route('admin.return.balance', $user->id) }}" class="btn btn-outline btn-error btn-sm">Return Balance</a>
                    </div>
                </div>

                @if (session('error'))
                <div class="alert alert-error mb-4">
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if ($errors->any())
                <div class="alert alert-error mb-4">
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="bg-linear-to-r from-primary/10 to-secondary/10 p-6 rounded-lg mb-6">
                    <h3 class="font-bold text-lg mb-4">User Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex justify-between">
                            <span class="font-semibold">Name:</span>
                            <span>{{ $user->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">Email:</span>
                            <span>{{ $user->email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">Main Balance:</span>
                            <span class="text-primary font-bold">৳{{ number_format($user->main_bal ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">Drive Balance:</span>
                            <span class="text-secondary font-bold">৳{{ number_format($user->drive_bal ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">Bank Balance:</span>
                            <span class="text-accent font-bold">৳{{ number_format($user->bank_bal ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.store.balance', $user->id) }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Balance Type</span>
                            </label>
                            <select name="balance_type" class="select select-bordered" required>
                                <option value="">Select Balance Type</option>
                                <option value="main_bal" {{ old('balance_type') === 'main_bal' ? 'selected' : '' }}>Main Balance</option>
                                <option value="drive_bal" {{ old('balance_type') === 'drive_bal' ? 'selected' : '' }}>Drive Balance</option>
                                <option value="bank_bal" {{ old('balance_type') === 'bank_bal' ? 'selected' : '' }}>Bank Balance</option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Amount</span>
                            </label>
                            <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" class="input input-bordered" placeholder="Enter amount" required />
                        </div>

                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Description</span>
                            </label>
                            <textarea name="description" rows="3" class="textarea textarea-bordered" placeholder="Why are you adding this balance?">{{ old('description') }}</textarea>
                        </div>

                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Admin PIN</span>
                            </label>
                            <input type="password" name="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" class="input input-bordered" placeholder="Enter 4 digit admin PIN" required />
                        </div>
                    </div>

                    <div class="flex gap-4 mt-6">
                        <button type="submit" class="btn btn-primary flex-1">Add Balance</button>
                        <a href="{{ route('admin.resellers') }}" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>