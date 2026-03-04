<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Drive Request - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center">
    <div class="container mx-auto p-6 max-w-2xl">
        <div class="card bg-base-100 shadow-2xl">
            <div class="card-body">
                <h2 class="card-title text-3xl mb-6 text-center justify-center">Confirm Drive Request</h2>

                @if(session('error'))
                    <div class="alert alert-warning mb-4">
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                
                <div class="bg-gradient-to-r from-primary/10 to-secondary/10 p-6 rounded-lg mb-6">
                    <h3 class="font-bold text-lg mb-4">Request Information</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="flex justify-between">
                            <span class="font-semibold">User:</span>
                            <span>{{ $request->user->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">Operator:</span>
                            <span class="badge badge-primary">{{ $request->operator }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">Package:</span>
                            <span>{{ $request->package->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">Mobile:</span>
                            <span>{{ $request->mobile }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">Amount:</span>
                            <span class="text-primary font-bold">৳{{ number_format($request->amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="/admin/drive-requests/{{ $request->id }}/confirm">
                    @csrf
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Description</span>
                        </label>
                        <textarea name="description" class="textarea textarea-bordered h-12 text-sm" placeholder="Enter description (optional)"></textarea>
                    </div>

                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Admin PIN</span>
                        </label>
                        <input type="password" name="pin" maxlength="4" pattern="[0-9]{4}" class="input input-bordered" placeholder="Enter your 4 digit PIN" required />
                        @error('pin')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-success flex-1">Confirm Success</button>
                        <a href="{{ route('admin.pending.drive.requests') }}" class="btn btn-error flex-1">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
