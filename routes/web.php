<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ApiDomain;
use App\Models\Branding;
use App\Models\DepositSetting;
use App\Models\HomepageSetting;
use App\Models\ManualPaymentRequest;
use App\Models\RechargeBlockList;
use App\Models\SslCommerzTransaction;
use App\Models\User;
use App\Http\Controllers\Admin\DeviceLogController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Admin\NoticeController;
use App\Services\GoogleOtpService;
use App\Services\FirebasePushNotificationService;
use App\Services\SecurityRuntimeService;
use App\Services\SslCommerzService;


$normalizeFlexiOperatorName = static function (?string $value): string {
    return strtolower(preg_replace('/[^a-z]/i', '', (string) $value));
};

$resolveSslCommerzTransaction = static function (Request $request): ?SslCommerzTransaction {
    $tranId = trim((string) ($request->input('tran_id') ?: $request->input('transaction_id')));

    if ($tranId === '' || ! Schema::hasTable('sslcommerz_transactions')) {
        return null;
    }

    return SslCommerzTransaction::query()
        ->with('user')
        ->where('tran_id', $tranId)
        ->first();
};

$sslCommerzRedirectResponse = static function (?SslCommerzTransaction $transaction, string $flashKey, string $message) {
    $user = Auth::user();

    if (
        $user
        && $transaction
        && (int) $user->getAuthIdentifier() === (int) $transaction->user_id
        && $user->hasPermission('add_balance')
    ) {
        return redirect()->route('user.add.balance')->with($flashKey, $message);
    }

    return redirect()
        ->route('user.add.balance.sslcommerz.status', array_filter([
            'tranId' => $transaction?->tran_id,
        ]))
        ->with($flashKey, $message);
};

$resolveManualPaymentMethods = static function (?Branding $branding) {
    return collect([
        ['key' => 'bkash', 'name' => 'Bkash', 'number' => optional($branding)->bkash, 'color' => 'bg-pink-500', 'route_name' => 'user.bkash'],
        ['key' => 'rocket', 'name' => 'Rocket', 'number' => optional($branding)->rocket, 'color' => 'bg-violet-600', 'route_name' => 'user.rocket'],
        ['key' => 'nagad', 'name' => 'Nagad', 'number' => optional($branding)->nagad, 'color' => 'bg-orange-500', 'route_name' => 'user.nagad'],
        ['key' => 'upay', 'name' => 'Upay', 'number' => optional($branding)->upay, 'color' => 'bg-emerald-600', 'route_name' => 'user.upay'],
    ]);
};

$resolveManualPaymentRedirectRoute = static function (?string $routeName): string {
    return in_array($routeName, ['user.add.balance', 'user.bkash', 'user.nagad', 'user.rocket', 'user.upay'], true)
        ? $routeName
        : 'user.add.balance';
};

$renderUserAddBalancePage = static function (?string $selectedManualMethodKey = null) use ($resolveManualPaymentMethods) {
    $settings = HomepageSetting::first();
    $branding = Branding::first();
    $user = Auth::user();
    $allManualMethods = $resolveManualPaymentMethods($branding);
    $manualMethods = $allManualMethods
        ->filter(fn(array $method) => filled($method['number']))
        ->values();
    $selectedManualMethod = filled($selectedManualMethodKey)
        ? $allManualMethods->firstWhere('key', strtolower(trim((string) $selectedManualMethodKey)))
        : null;

    $recentRequests = collect();
    $recentSslCommerzTransactions = collect();

    if (Schema::hasTable('manual_payment_requests') && filled($user?->getAuthIdentifier())) {
        $recentRequests = ManualPaymentRequest::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->when(
                filled(data_get($selectedManualMethod, 'name')),
                fn($query) => $query->where('method', data_get($selectedManualMethod, 'name'))
            )
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (ManualPaymentRequest $request) use ($user) {
                $amount = round((float) $request->amount, 2);
                $bonusPercent = DepositSetting::bonusPercent($user?->level, $request->method);

                $request->cost = round($amount + (($amount * $bonusPercent) / 100), 2);

                return $request;
            });
    }

    if (Schema::hasTable('sslcommerz_transactions') && filled($user?->getAuthIdentifier())) {
        $recentSslCommerzTransactions = SslCommerzTransaction::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->latest()
            ->limit(10)
            ->get();
    }

    return view('user-add-balance', compact(
        'settings',
        'branding',
        'user',
        'manualMethods',
        'recentRequests',
        'recentSslCommerzTransactions',
        'selectedManualMethod'
    ));
};

$settleSslCommerzTransaction = static function (SslCommerzTransaction $transaction, array $callbackPayload = []): array {
    if ($transaction->credited_at !== null) {
        if (! empty($callbackPayload)) {
            $transaction->update([
                'callback_payload' => array_merge($transaction->callback_payload ?? [], $callbackPayload),
            ]);
        }

        return [
            'ok' => true,
            'credited' => false,
            'message' => 'SSLCommerz payment already processed.',
            'transaction' => $transaction->fresh(['user']),
        ];
    }

    $branding = Branding::first();
    $sslCommerzService = app(SslCommerzService::class);

    if (! $branding || ! $sslCommerzService->isConfigured($branding)) {
        return [
            'ok' => false,
            'credited' => false,
            'message' => 'SSLCommerz is not configured right now.',
            'transaction' => $transaction,
        ];
    }

    $validation = $sslCommerzService->validateTransaction($branding, [
        'val_id' => $callbackPayload['val_id'] ?? null,
        'tran_id' => $transaction->tran_id,
        'amount' => $transaction->amount,
        'currency' => $transaction->currency ?: 'BDT',
    ]);

    $gatewayData = $validation['data'] ?? [];
    $validatedAmount = round((float) ($gatewayData['amount'] ?? $gatewayData['store_amount'] ?? $transaction->amount), 2);
    $expectedAmount = round((float) $transaction->amount, 2);

    if (! $validation['ok']) {
        $transaction->update([
            'status' => 'failed',
            'gateway_status' => strtolower(trim((string) ($gatewayData['status'] ?? 'validation_failed'))),
            'validation_payload' => $gatewayData,
            'callback_payload' => array_merge($transaction->callback_payload ?? [], $callbackPayload),
            'failure_reason' => $validation['message'] ?? 'SSLCommerz validation failed.',
        ]);

        return [
            'ok' => false,
            'credited' => false,
            'message' => $validation['message'] ?? 'SSLCommerz validation failed.',
            'transaction' => $transaction->fresh(['user']),
        ];
    }

    if (abs($validatedAmount - $expectedAmount) > 0.01) {
        $transaction->update([
            'status' => 'failed',
            'gateway_status' => strtolower(trim((string) ($gatewayData['status'] ?? 'amount_mismatch'))),
            'validated_amount' => $validatedAmount,
            'validation_payload' => $gatewayData,
            'callback_payload' => array_merge($transaction->callback_payload ?? [], $callbackPayload),
            'failure_reason' => 'Validated amount does not match the requested amount.',
        ]);

        return [
            'ok' => false,
            'credited' => false,
            'message' => 'Validated amount does not match the requested amount.',
            'transaction' => $transaction->fresh(['user']),
        ];
    }

    $credited = false;

    DB::transaction(function () use (&$credited, $transaction, $callbackPayload, $gatewayData, $validatedAmount, $expectedAmount) {
        $lockedTransaction = SslCommerzTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
        $mergedCallbackPayload = array_merge($lockedTransaction->callback_payload ?? [], $callbackPayload);

        if ($lockedTransaction->credited_at !== null) {
            $lockedTransaction->update([
                'callback_payload' => $mergedCallbackPayload,
            ]);

            return;
        }

        $lockedTransaction->update([
            'status' => 'approved',
            'gateway_status' => strtolower(trim((string) ($gatewayData['status'] ?? 'validated'))),
            'validated_amount' => $validatedAmount,
            'bank_tran_id' => trim((string) ($gatewayData['bank_tran_id'] ?? '')) ?: null,
            'card_type' => trim((string) ($gatewayData['card_type'] ?? '')) ?: null,
            'store_amount' => round((float) ($gatewayData['store_amount'] ?? $expectedAmount), 2),
            'validation_id' => trim((string) ($gatewayData['val_id'] ?? '')) ?: null,
            'validation_payload' => $gatewayData,
            'callback_payload' => $mergedCallbackPayload,
            'failure_reason' => null,
            'validated_at' => now(),
            'credited_at' => now(),
        ]);

        $user = User::query()->lockForUpdate()->findOrFail($lockedTransaction->user_id);
        $user->main_bal = round((float) $user->main_bal + $expectedAmount, 2);
        $user->save();

        if (Schema::hasTable('balance_add_history')) {
            $payload = [
                'user_id' => $user->id,
                'amount' => $expectedAmount,
                'type' => 'sslcommerz',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('balance_add_history', 'description')) {
                $payload['description'] = 'SSLCommerz payment approved. TxID: ' . $lockedTransaction->tran_id;
            }

            DB::table('balance_add_history')->insert($payload);
        }

        $credited = true;
    });

    $freshTransaction = SslCommerzTransaction::query()->with('user')->find($transaction->id);

    if ($credited && $freshTransaction?->user) {
        app(FirebasePushNotificationService::class)->sendToUser(
            $freshTransaction->user,
            'Balance Added via SSLCommerz',
            'Your SSLCommerz payment of ' . number_format($expectedAmount, 2) . ' Tk was verified and added to your main balance.',
            route('dashboard'),
        );
    }

    return [
        'ok' => true,
        'credited' => $credited,
        'message' => $credited
            ? 'SSLCommerz payment verified and balance added successfully.'
            : 'SSLCommerz payment already processed.',
        'transaction' => $freshTransaction,
    ];
};

$flexiOperatorPrefixes = [
    'grameenphone' => ['017', '013'],
    'robi' => ['018'],
    'airtel' => ['016'],
    'banglalink' => ['019', '014'],
    'teletalk' => ['015'],
];

$resolveFlexiOperatorFromMobile = static function (string $mobile, array $operators) use ($flexiOperatorPrefixes, $normalizeFlexiOperatorName): ?array {
    $prefix = substr(preg_replace('/[^0-9]/', '', $mobile), 0, 3);

    if (strlen($prefix) !== 3) {
        return null;
    }

    $matchedKey = null;

    foreach ($flexiOperatorPrefixes as $operatorKey => $prefixes) {
        if (in_array($prefix, $prefixes, true)) {
            $matchedKey = $operatorKey;
            break;
        }
    }

    if ($matchedKey === null) {
        return null;
    }

    foreach ($operators as $operator) {
        $operatorKey = $normalizeFlexiOperatorName((string) ($operator['route_name'] ?? $operator['name'] ?? ''));

        if ($operatorKey === $matchedKey) {
            return $operator;
        }
    }

    return null;
};

$normalizeRechargeBlockOperator = static function (?string $value): string {
    $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $value));

    return match ($normalized) {
        'GRAMEENPHONE', 'GP' => 'GP',
        'ROBI', 'RB' => 'RB',
        'AIRTEL', 'AT' => 'AT',
        'BANGLALINK', 'BL' => 'BL',
        'TELETALK', 'TT' => 'TT',
        default => substr($normalized, 0, 20),
    };
};

$isRechargeAmountBlocked = static function (string $service, ?string $operator, $amount) use ($normalizeRechargeBlockOperator): bool {
    if (! Schema::hasTable('recharge_block_lists')) {
        return false;
    }

    $normalizedOperator = $normalizeRechargeBlockOperator($operator);

    if ($normalizedOperator === '') {
        return false;
    }

    return RechargeBlockList::query()
        ->where('service', $service)
        ->where('operator', $normalizedOperator)
        ->where('amount', round((float) $amount, 2))
        ->exists();
};


// Api route
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'prevent.back'])->group(function () {

    Route::get('/device-logs', [DeviceLogController::class, 'index'])->name('device.logs');
    Route::post('/device-logs/{id}/approve', [DeviceLogController::class, 'approve'])->name('device.logs.approve');
    Route::delete('/device-logs/{id}', [DeviceLogController::class, 'destroy'])->name('device.logs.destroy');
});

Route::prefix('admin')->group(function () {
    Route::get('/notice', [NoticeController::class, 'index'])->name('admin.notice.index');
    Route::post('/notice/update', [NoticeController::class, 'update'])->name('admin.notice.update');
});



Route::get('/', [HomepageController::class, 'index'])->name('homepage');
// Admin routes
Route::middleware(['auth', 'admin', 'prevent.back'])->group(function () {
    Route::get('/api-settings', [ApiController::class, 'index'])->name('api.index');
    Route::post('/api-settings/connections', [ApiController::class, 'storeConnection'])->name('api.connections.store');
    Route::put('/api-settings/connections/{connection}', [ApiController::class, 'updateConnection'])->name('api.connections.update');
    Route::delete('/api-settings/connections/{connection}', [ApiController::class, 'destroyConnection'])->name('api.connections.destroy');
    Route::get('/api-settings/connections/{connection}/route', [ApiController::class, 'openConnectionRoute'])->name('api.connections.route');
    Route::get('/api-settings/routes', [ApiController::class, 'routeIndex'])->name('api.routes.index');
    Route::post('/api-settings/routes', [ApiController::class, 'storeRoute'])->name('api.routes.store');
    Route::put('/api-settings/routes/{apiRoute}', [ApiController::class, 'updateRoute'])->name('api.routes.update');
    Route::delete('/api-settings/routes/{apiRoute}', [ApiController::class, 'destroyRoute'])->name('api.routes.destroy');
    Route::post('/api-settings/connections/{connection}/balance-check', [ApiController::class, 'balanceCheck'])->name('api.connections.balance');
    Route::post('/api-settings/{user}', [ApiController::class, 'store'])->name('api.store');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/branding', [BrandingController::class, 'index'])->name('branding');
    Route::post('/branding/update', [BrandingController::class, 'update'])->name('branding.update');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'prevent.back'])->group(function () {
    Route::get('/deposit', [BrandingController::class, 'deposit'])->name('deposit');
    Route::post('/deposit', [BrandingController::class, 'updateDeposit'])->name('deposit.update');
    Route::get('/payment-gateway', [BrandingController::class, 'paymentGateway'])->name('payment.gateway');
    Route::post('/payment-gateway/update', [BrandingController::class, 'updatePaymentGateway'])->name('payment.gateway.update');
});

Route::get('/complaints', [ComplaintController::class, 'index'])
    ->middleware(['auth', 'prevent.back', 'permission:complaints'])
    ->name('complaints.index');
Route::post('/complaints/store', [ComplaintController::class, 'store'])
    ->middleware(['auth', 'prevent.back', 'permission:complaints'])
    ->name('complaints.store');

// Admin Complaint Routes
Route::get('/admin/complaints', [ComplaintController::class, 'adminIndex'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.complaints');
Route::post('/admin/complaints/{id}/reply', [ComplaintController::class, 'adminReply'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.complaints.reply');

Route::get('/admin', [AdminController::class, 'dashboard'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.dashboard');
Route::get('/admin/resellers', [AdminController::class, 'resellers'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.resellers');

Route::get('/admin/resellers/{user}', [AdminController::class, 'showReseller'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.resellers.show');

Route::put('/admin/resellers/{user}', [AdminController::class, 'updateReseller'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.resellers.update');

Route::get('/admin/all-resellers', [AdminController::class, 'allResellers'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.all.resellers');

Route::post('/admin/resellers/bulk-action', [AdminController::class, 'bulkResellerAction'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.resellers.bulk-action');

Route::get('/admin/deleted-accounts', [AdminController::class, 'deletedAccounts'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.deleted.accounts');

Route::post('/admin/deleted-accounts/{userId}/restore', [AdminController::class, 'restoreDeletedAccount'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.deleted.accounts.restore');

Route::get('/admin/add-balance/{userId}', [AdminController::class, 'addBalance'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.add.balance');

Route::post('/admin/add-balance/{userId}', [AdminController::class, 'storeBalance'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.store.balance');

Route::get('/admin/return-balance/{userId}', [AdminController::class, 'returnBalance'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.return.balance');

Route::post('/admin/return-balance/{userId}', [AdminController::class, 'storeReturnBalance'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.store.return.balance');

Route::post('/admin/resellers/{user}/toggle', [AdminController::class, 'toggleStatus'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.resellers.toggle');

Route::post('/admin/users/store', [AdminController::class, 'storeUser'])
    ->middleware(['auth', 'admin', 'prevent.back', 'permission:manage_resellers'])
    ->name('admin.users.store');

Route::get('/admin/backup', [AdminController::class, 'backup'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.backup');

Route::get('/admin/backup/download', [AdminController::class, 'downloadBackup'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.backup.download');

Route::get('/admin/profile', [AdminController::class, 'profile'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.profile');

Route::get('/admin/profile/edit', [AdminController::class, 'editProfile'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.profile.edit');

Route::put('/admin/profile', [AdminController::class, 'updateProfile'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.profile.update');

Route::put('/admin/profile/picture', [AdminController::class, 'updateProfilePicture'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.profile.picture.update');

Route::delete('/admin/profile/picture', [AdminController::class, 'deleteProfilePicture'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.profile.picture.delete');

Route::post('/admin/profile/google-otp/enable', [AdminController::class, 'enableGoogleOtp'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.profile.google-otp.enable');

Route::post('/admin/profile/google-otp/disable', [AdminController::class, 'disableGoogleOtp'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.profile.google-otp.disable');

Route::get('/admin/manage-admins', [AdminController::class, 'manageAdmins'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.admins');

Route::post('/admin/store-admin', [AdminController::class, 'storeAdmin'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.store.admin');

Route::get('/admin/change-credentials', [AdminController::class, 'showChangeCredentials'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.change.credentials');

Route::post('/admin/update-password', [AdminController::class, 'updatePassword'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.update.password');

Route::post('/admin/update-pin', [AdminController::class, 'updatePin'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.update.pin');

Route::get('/admin/drive-offer', [AdminController::class, 'driveOffer'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.offer');

Route::get('/admin/regular-offer', [AdminController::class, 'regularOffer'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.regular.offer');

Route::get('/admin/manage-regular-package/{operator}', [AdminController::class, 'manageRegularPackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.regular.package');

Route::get('/admin/manage-regular-package/{operator}/{id}/edit', [AdminController::class, 'editRegularPackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.regular.package.edit');

Route::post('/admin/manage-regular-package/{operator}/store', [AdminController::class, 'storeRegularPackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.regular.package.store');

Route::post('/admin/manage-regular-package/{operator}/{id}/update', [AdminController::class, 'updateRegularPackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.regular.package.update.save');

Route::post('/admin/manage-regular-package/{operator}', [AdminController::class, 'updateRegularPackageFromApi'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.regular.package.update');

Route::get('/admin/manage-drive-package/{operator}', [AdminController::class, 'manageDrivePackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.drive.package');

Route::get('/admin/manage-drive-package/{operator}/{id}/edit', [AdminController::class, 'editDrivePackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.drive.package.edit');

Route::post('/admin/manage-drive-package/{operator}/store', [AdminController::class, 'storeDrivePackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.drive.package.store');

Route::post('/admin/manage-drive-package/{operator}/{id}/update', [AdminController::class, 'updateDrivePackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.drive.package.update.save');

Route::post('/admin/manage-drive-package/{operator}', [AdminController::class, 'updateDrivePackageFromApi'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.drive.package.update');

Route::get('/admin/pending-drive-requests', [AdminController::class, 'pendingDriveRequests'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.pending.drive.requests');

Route::post('/admin/pending-drive-requests/bulk-action', [AdminController::class, 'bulkPendingRequestAction'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.pending.requests.bulk-action');

Route::post('/admin/drive-requests/{id}/approve', [AdminController::class, 'approveDriveRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.request.approve');

Route::post('/admin/drive-requests/{id}/confirm', [AdminController::class, 'confirmDriveRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.request.confirm');

Route::post('/admin/drive-requests/{id}/reject', [AdminController::class, 'rejectDriveRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.request.reject');

Route::post('/admin/drive-requests/{id}/failed', [AdminController::class, 'rejectDriveRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.request.failed');

Route::post('/admin/drive-requests/{id}/confirm-failed', [AdminController::class, 'confirmFailedRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.request.confirm.failed');

Route::post('/admin/drive-requests/{id}/cancel', [AdminController::class, 'cancelDriveRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.request.cancel');

Route::post('/admin/drive-requests/{id}/confirm-cancel', [AdminController::class, 'confirmCancelRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.request.confirm.cancel');

Route::post('/admin/regular-requests/{id}/approve', [AdminController::class, 'approveRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.regular.request.approve');

Route::get('/admin/regular-requests/{id}/approve', [AdminController::class, 'approveRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/regular-requests/{id}/confirm', [AdminController::class, 'confirmRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.regular.request.confirm');

Route::post('/admin/regular-requests/{id}/failed', [AdminController::class, 'rejectRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.regular.request.failed');

Route::get('/admin/regular-requests/{id}/failed', [AdminController::class, 'rejectRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/regular-requests/{id}/confirm-failed', [AdminController::class, 'confirmFailedRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.regular.request.confirm.failed');

Route::post('/admin/regular-requests/{id}/cancel', [AdminController::class, 'cancelRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.regular.request.cancel');

Route::get('/admin/regular-requests/{id}/cancel', [AdminController::class, 'cancelRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/regular-requests/{id}/confirm-cancel', [AdminController::class, 'confirmCancelRegularRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.regular.request.confirm.cancel');

Route::post('/admin/flexi-requests/{id}/approve', [AdminController::class, 'approveFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.flexi.request.approve');

Route::get('/admin/flexi-requests/{id}/approve', [AdminController::class, 'approveFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/flexi-requests/{id}/confirm', [AdminController::class, 'confirmFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.flexi.request.confirm');

Route::post('/admin/flexi-requests/{id}/failed', [AdminController::class, 'rejectFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.flexi.request.failed');

Route::get('/admin/flexi-requests/{id}/failed', [AdminController::class, 'rejectFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/flexi-requests/{id}/confirm-failed', [AdminController::class, 'confirmFailedFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.flexi.request.confirm.failed');

Route::post('/admin/flexi-requests/{id}/cancel', [AdminController::class, 'cancelFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.flexi.request.cancel');

Route::get('/admin/flexi-requests/{id}/cancel', [AdminController::class, 'cancelFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/flexi-requests/{id}/confirm-cancel', [AdminController::class, 'confirmCancelFlexiRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.flexi.request.confirm.cancel');

Route::post('/admin/manual-payment-requests/{id}/approve', [AdminController::class, 'approveManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manual.payment.request.approve');

Route::get('/admin/manual-payment-requests/{id}/approve', [AdminController::class, 'approveManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/manual-payment-requests/{id}/confirm', [AdminController::class, 'confirmManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manual.payment.request.confirm');

Route::post('/admin/manual-payment-requests/{id}/failed', [AdminController::class, 'failedManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manual.payment.request.failed');

Route::get('/admin/manual-payment-requests/{id}/failed', [AdminController::class, 'failedManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/manual-payment-requests/{id}/confirm-failed', [AdminController::class, 'confirmFailedManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manual.payment.request.confirm.failed');

Route::post('/admin/manual-payment-requests/{id}/cancel', [AdminController::class, 'cancelManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manual.payment.request.cancel');

Route::get('/admin/manual-payment-requests/{id}/cancel', [AdminController::class, 'cancelManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back']);

Route::post('/admin/manual-payment-requests/{id}/confirm-cancel', [AdminController::class, 'confirmCancelManualPaymentRequest'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manual.payment.request.confirm.cancel');

Route::get('/admin/drive-history', [AdminController::class, 'driveHistory'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.history');

Route::get('/admin/service-modules', [AdminController::class, 'serviceModules'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.service.modules');

Route::post('/admin/service-modules', [AdminController::class, 'storeServiceModule'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.service.modules.store');

Route::put('/admin/service-modules/{serviceModule}', [AdminController::class, 'updateServiceModule'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.service.modules.update');

Route::get('/admin/recharge-block-list', [AdminController::class, 'rechargeBlockList'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.recharge.block.list');

Route::post('/admin/recharge-block-list', [AdminController::class, 'storeRechargeBlockList'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.recharge.block.list.store');

Route::delete('/admin/recharge-block-list/{rechargeBlockList}', [AdminController::class, 'destroyRechargeBlockList'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.recharge.block.list.destroy');

Route::get('/admin/security-modual', [AdminController::class, 'securityModual'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.security.modual');

Route::post('/admin/security-modual', [AdminController::class, 'updateSecurityModual'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.security.modual.update');

Route::get('/admin/balance-report', [AdminController::class, 'balanceReport'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.balance.report');

Route::get('/admin/daily-reports', [AdminController::class, 'dailyReports'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.daily.reports');

Route::get('/admin/operator-reports', [AdminController::class, 'operatorReports'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.operator.reports');

Route::get('/admin/sales-report', [AdminController::class, 'salesReport'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.sales.report');

Route::post('/admin/manage-drive-package/{operator}/store', [AdminController::class, 'storeDrivePackage'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.manage.drive.package.store');

Route::get('/admin/all-history', [AdminController::class, 'allHistory'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.all.history');

Route::get('/admin/internet-history', [AdminController::class, 'internetHistory'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.internet.history');

Route::delete('/admin/all-history/delete-all', [AdminController::class, 'deleteAllHistory'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.all.history.delete');

Route::get('/admin/operator/create', [\App\Http\Controllers\AdminController::class, 'createOperator'])->name('admin.operator.create');

Route::post('/admin/operator/store', [AdminController::class, 'storeOperator'])->name('admin.operator.store');

Route::post('/admin/regular-offer/store', [AdminController::class, 'storeRegularOffer'])->name('admin.regular.offer.store');
Route::post('/admin/drive-offer/store', [AdminController::class, 'storeDriveOffer'])->name('admin.drive.offer.store');

Route::get('/admin/homepage', [HomepageController::class, 'edit'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.homepage.edit');
Route::post('/admin/homepage', [HomepageController::class, 'update'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.homepage.update');

Route::post('/admin/settings/updateLogos', [HomepageController::class, 'updateLogos'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.settings.updateLogos');

Route::get('/admin/mail-config', [HomepageController::class, 'mailConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.mail.config');
Route::post('/admin/mail-config', [HomepageController::class, 'updateMailConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.mail.update');

Route::get('/admin/sms-config', [HomepageController::class, 'smsConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.sms.config');
Route::post('/admin/sms-config', [HomepageController::class, 'updateSmsConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.sms.update');

Route::get('/admin/firebase-config', [HomepageController::class, 'firebaseConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.firebase.config');
Route::post('/admin/firebase-config', [HomepageController::class, 'updateFirebaseConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.firebase.update');

Route::get('/admin/google-otp-config', [HomepageController::class, 'googleOtpConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.google.otp.config');
Route::post('/admin/google-otp-config', [HomepageController::class, 'updateGoogleOtpConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.google.otp.update');

Route::get('/admin/recaptcha-config', [HomepageController::class, 'recaptchaConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.recaptcha.config');
Route::post('/admin/recaptcha-config', [HomepageController::class, 'updateRecaptchaConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.recaptcha.update');

Route::get('/admin/tawk-chat-config', [HomepageController::class, 'tawkChatConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.tawk.config');
Route::post('/admin/tawk-chat-config', [HomepageController::class, 'updateTawkChatConfig'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.tawk.update');

Route::get('/notifications/firebase/bootstrap', [HomepageController::class, 'firebaseBootstrap'])
    ->name('notifications.firebase.bootstrap');
Route::post('/notifications/firebase/token', [HomepageController::class, 'registerFcmToken'])
    ->middleware(['auth'])
    ->name('notifications.firebase.token');
Route::get('/firebase-messaging-sw.js', [HomepageController::class, 'firebaseMessagingServiceWorker'])
    ->name('notifications.firebase.worker');

Route::get('/login', [AuthPageController::class, 'login'])->name('login');
Route::post('/login', [AuthPageController::class, 'handleLogin']);
Route::get('/login/otp', [AuthPageController::class, 'showOtpChallenge'])->name('login.otp.show');
Route::post('/login/otp', [AuthPageController::class, 'verifyOtpChallenge'])->name('login.otp.verify');

Route::get('/register', [AuthPageController::class, 'register'])->name('register');
Route::post('/register', [AuthPageController::class, 'handleRegister']);
Route::post('/send-registration-otp', [AuthPageController::class, 'sendRegistrationOtp'])->name('send.registration.otp');
Route::post('/send-registration-otp-mobile', [AuthPageController::class, 'sendRegistrationOtpMobile'])->name('send.registration.otp.mobile');

Route::get('/forgot-password', [AuthPageController::class, 'showForgotPassword'])->name('forgot.password');
Route::post('/forgot-password', [AuthPageController::class, 'handleForgotPassword'])->name('forgot.password.submit');
Route::post('/send-forgot-password-otp', [AuthPageController::class, 'sendForgotPasswordOtp'])->name('send.forgot.password.otp');
Route::post('/send-forgot-password-otp-mobile', [AuthPageController::class, 'sendForgotPasswordOtpMobile'])->name('send.forgot.password.otp.mobile');

// logout endpoint used by admin navbar
Route::post('/logout', function () {
    $isAdmin = Auth::check() && Auth::user()->is_admin;
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect($isAdmin ? '/admin/login' : '/');
})->name('logout');

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'handleLogin']);
Route::get('/admin/login/otp', [AdminAuthController::class, 'showOtpChallenge'])->name('admin.login.otp.show');
Route::post('/admin/login/otp', [AdminAuthController::class, 'verifyOtpChallenge'])->name('admin.login.otp.verify');

Route::get('/dashboard', function () {
    $settings = \App\Models\HomepageSetting::first();
    $user = Auth::user();
    $pendingDriveRequests = \App\Models\DriveRequest::where('user_id', $user->id)
        ->where('status', 'pending')
        ->with('package')
        ->get()
        ->map(function ($item) {
            $item->request_type = 'Drive';
            return $item;
        });

    $pendingRegularRequests = \App\Models\RegularRequest::where('user_id', $user->id)
        ->where('status', 'pending')
        ->with('package')
        ->get();

    $pendingRegularRequests = $pendingRegularRequests->map(function ($item) {
        $item->request_type = 'Internet';
        return $item;
    });

    $pendingFlexiRequests = collect();

    if (\Illuminate\Support\Facades\Schema::hasTable('flexi_requests')) {
        $pendingFlexiRequests = \App\Models\FlexiRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->get()
            ->map(function ($item) {
                $item->request_type = 'Flexi';
                return $item;
            });
    }

    $pendingRequests = $pendingDriveRequests
        ->concat($pendingRegularRequests)
        ->concat($pendingFlexiRequests)
        ->sortByDesc('created_at')
        ->values();

    // Get last received balance history for the logged-in user
    $lastReceived = \DB::table('balance_add_history')
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    $usageEntries = collect();

    if (\Illuminate\Support\Facades\Schema::hasTable('drive_history')) {
        $usageEntries = $usageEntries->concat(
            \DB::table('drive_history')
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'amount' => (float) ($item->amount ?? 0),
                        'operator' => $item->operator ?? 'Drive',
                        'created_at' => \Carbon\Carbon::parse($item->created_at),
                    ];
                })
        );
    }

    if (\Illuminate\Support\Facades\Schema::hasTable('regular_requests')) {
        $usageEntries = $usageEntries->concat(
            \App\Models\RegularRequest::query()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'amount' => (float) ($item->amount ?? 0),
                        'operator' => $item->operator ?? 'Internet Pack',
                        'created_at' => \Carbon\Carbon::parse($item->created_at),
                    ];
                })
        );
    }

    if (\Illuminate\Support\Facades\Schema::hasTable('recharge_history')) {
        $usageEntries = $usageEntries->concat(
            \DB::table('recharge_history')
                ->where('user_id', $user->id)
                ->where(function ($query) {
                    $query->whereNull('type')
                        ->orWhere('type', 'not like', 'Internet Pack%');
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'amount' => (float) ($item->amount ?? 0),
                        'operator' => $item->type ?? 'Recharge',
                        'created_at' => \Carbon\Carbon::parse($item->created_at),
                    ];
                })
        );
    }

    $usageEntries = $usageEntries->sortByDesc('created_at')->values();
    $latestUsage = $usageEntries->first();
    $oldestUsage = $usageEntries->last();

    if ($latestUsage && $oldestUsage) {
        $periodLabel = $latestUsage->created_at->isSameDay($oldestUsage->created_at)
            ? $latestUsage->created_at->format('d M Y')
            : $oldestUsage->created_at->format('d M Y') . ' - ' . $latestUsage->created_at->format('d M Y');
    } else {
        $periodLabel = 'No recharge history yet';
    }

    $usageStats = [
        'total_spent' => round($usageEntries->sum('amount'), 2),
        'total_recharges' => $usageEntries->count(),
        'period_label' => $periodLabel,
        'recharge_desc' => $usageEntries->isNotEmpty()
            ? 'Successful requests and recharges'
            : 'Start with your first successful recharge',
        'last_recharge_label' => $latestUsage
            ? $latestUsage->created_at->diffForHumans()
            : 'No recharge yet',
        'last_recharge_operator' => $latestUsage
            ? 'on ' . $latestUsage->operator
            : 'No successful recharge found',
    ];

    return view('dashboard', [
        'user' => $user,
        'settings' => $settings,
        'pendingRequests' => $pendingRequests,
        'lastReceived' => $lastReceived,
        'usageStats' => $usageStats,
        'apiDocs' => \App\Http\Controllers\HomepageController::providerApiDocs(),
    ]);
})->middleware(['auth', 'prevent.back'])->name('dashboard');

Route::get('/add-balance', function () use ($renderUserAddBalancePage) {
    return $renderUserAddBalancePage();
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.add.balance');

Route::get('/bkash', function () use ($renderUserAddBalancePage) {
    return $renderUserAddBalancePage('bkash');
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.bkash');

Route::get('/nagad', function () use ($renderUserAddBalancePage) {
    return $renderUserAddBalancePage('nagad');
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.nagad');

Route::get('/rocket', function () use ($renderUserAddBalancePage) {
    return $renderUserAddBalancePage('rocket');
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.rocket');

Route::get('/upay', function () use ($renderUserAddBalancePage) {
    return $renderUserAddBalancePage('upay');
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.upay');

Route::post('/add-balance', function (Request $request) use ($resolveManualPaymentMethods, $resolveManualPaymentRedirectRoute) {
    $redirectRoute = $resolveManualPaymentRedirectRoute($request->input('redirect_route'));

    if (! \Illuminate\Support\Facades\Schema::hasTable('manual_payment_requests')) {
        return redirect()->route($redirectRoute)->with('error', 'Manual payment request system is not ready yet.');
    }

    $branding = Branding::first();
    $availableMethods = $resolveManualPaymentMethods($branding)
        ->filter(fn(array $method) => filled($method['number']))
        ->pluck('name')
        ->values()
        ->all();

    $dedicatedRouteMethod = match ($redirectRoute) {
        'user.bkash' => 'Bkash',
        'user.nagad' => 'Nagad',
        'user.rocket' => 'Rocket',
        'user.upay' => 'Upay',
        default => null,
    };

    if (filled($dedicatedRouteMethod) && ! in_array($dedicatedRouteMethod, $availableMethods, true)) {
        $availableMethods[] = $dedicatedRouteMethod;
    }

    if (empty($availableMethods)) {
        return redirect()->route($redirectRoute)->with('error', 'No manual payment method is available right now.');
    }

    $typeOptions = ['Cash IN', 'cash out', 'send money'];

    $validated = $request->validate([
        'method' => ['required', \Illuminate\Validation\Rule::in($availableMethods)],
        'sender_number' => ['required', 'regex:/^01[0-9]{9}$/'],
        'amount' => ['required', 'numeric', 'min:500', 'max:25000'],
        'type' => ['required', \Illuminate\Validation\Rule::in($typeOptions)],
        'pin' => ['required', 'digits:4'],
    ]);

    $user = Auth::user();

    if (! $user || ! Hash::check((string) $validated['pin'], (string) $user->pin)) {
        return redirect()
            ->route($redirectRoute)
            ->withInput($request->except('pin'))
            ->withErrors(['pin' => 'Invalid PIN'])
            ->with('error', 'Invalid PIN');
    }

    $securityRuntime = app(SecurityRuntimeService::class);

    if ($securityRuntime->hasRecentRequest('manual_payment_requests', (int) auth()->id())) {
        $message = $securityRuntime->requestIntervalMessage();

        return redirect()
            ->route($redirectRoute)
            ->withInput($request->except('pin'))
            ->withErrors(['request' => $message])
            ->with('error', $message);
    }

    do {
        $generatedTransactionId = 'MB-' . Str::upper(Str::random(10));
    } while (ManualPaymentRequest::query()->where('transaction_id', $generatedTransactionId)->exists());

    ManualPaymentRequest::create([
        'user_id' => auth()->id(),
        'method' => trim((string) $validated['method']),
        'sender_number' => trim((string) $validated['sender_number']),
        'transaction_id' => $generatedTransactionId,
        'amount' => $validated['amount'],
        'note' => trim((string) $validated['type']),
        'status' => 'pending',
    ]);

    return redirect()->route($redirectRoute)->with('success', 'Manual payment request submitted successfully. Please wait for admin approval.');
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.add.balance.submit');

Route::post('/add-balance/sslcommerz/start', function (Request $request) {
    if (! Schema::hasTable('sslcommerz_transactions')) {
        return redirect()->route('user.add.balance')->with('error', 'SSLCommerz transaction table is missing. Please run php artisan migrate first.');
    }

    $branding = Branding::first();
    $sslCommerzService = app(SslCommerzService::class);

    if (! $branding || ! $sslCommerzService->isConfigured($branding)) {
        return redirect()->route('user.add.balance')->with('error', 'SSLCommerz is not configured right now.');
    }

    $validated = $request->validate([
        'sslcommerz_amount' => ['required', 'numeric', 'min:1'],
    ]);

    $securityRuntime = app(SecurityRuntimeService::class);

    if ($securityRuntime->hasRecentRequest('sslcommerz_transactions', (int) auth()->id())) {
        $message = $securityRuntime->requestIntervalMessage();

        return redirect()
            ->route('user.add.balance')
            ->withInput()
            ->withErrors(['sslcommerz_amount' => $message])
            ->with('error', $message);
    }

    $user = auth()->user();
    $settings = HomepageSetting::first();
    $amount = round((float) $validated['sslcommerz_amount'], 2);
    $transaction = SslCommerzTransaction::create([
        'user_id' => $user->id,
        'tran_id' => 'SSL-' . strtoupper(Str::random(12)),
        'amount' => $amount,
        'currency' => 'BDT',
        'status' => 'initiated',
        'request_payload' => [
            'requested_amount' => $amount,
            'requested_by' => $user->id,
        ],
    ]);

    $customerEmail = filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)
        ? (string) $user->email
        : 'customer@example.com';
    $customerPhone = preg_match('/^01[0-9]{9}$/', (string) $user->mobile)
        ? (string) $user->mobile
        : '01700000000';
    $customerName = trim((string) $user->name) !== '' ? trim((string) $user->name) : ('Customer ' . $user->id);
    $customerAddress = optional($settings)->company_name ?: (optional($branding)->brand_name ?: 'Bangladesh');

    $sessionPayload = [
        'total_amount' => number_format($amount, 2, '.', ''),
        'currency' => 'BDT',
        'tran_id' => $transaction->tran_id,
        'success_url' => route('user.add.balance.sslcommerz.success'),
        'fail_url' => route('user.add.balance.sslcommerz.fail'),
        'cancel_url' => route('user.add.balance.sslcommerz.cancel'),
        'ipn_url' => route('user.add.balance.sslcommerz.ipn'),
        'shipping_method' => 'NO',
        'product_name' => 'Add Balance',
        'product_category' => 'Deposit',
        'product_profile' => 'general',
        'cus_name' => $customerName,
        'cus_email' => $customerEmail,
        'cus_add1' => $customerAddress,
        'cus_city' => 'Dhaka',
        'cus_state' => 'Dhaka',
        'cus_postcode' => '1207',
        'cus_country' => 'Bangladesh',
        'cus_phone' => $customerPhone,
        'value_a' => (string) $user->id,
        'value_b' => $transaction->tran_id,
    ];

    $response = $sslCommerzService->initiateSession($branding, $sessionPayload);
    $responseData = $response['data'] ?? [];

    $transaction->update([
        'status' => $response['ok'] ? 'pending' : 'failed',
        'gateway_status' => strtolower(trim((string) ($responseData['status'] ?? ($response['ok'] ? 'success' : 'failed')))),
        'session_key' => trim((string) ($responseData['sessionkey'] ?? $responseData['session_key'] ?? '')) ?: null,
        'gateway_url' => trim((string) ($responseData['GatewayPageURL'] ?? $responseData['redirectGatewayURL'] ?? '')) ?: null,
        'request_payload' => $sessionPayload,
        'init_response_payload' => $responseData,
        'failure_reason' => $response['ok'] ? null : ($response['message'] ?? 'SSLCommerz session creation failed.'),
    ]);

    if (! $response['ok'] || blank($transaction->gateway_url)) {
        return redirect()->route('user.add.balance')->with('error', $response['message'] ?? 'Unable to initiate SSLCommerz payment right now.');
    }

    return redirect()->away($transaction->gateway_url);
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.add.balance.sslcommerz.start');

Route::get('/add-balance/sslcommerz/status/{tranId?}', function (?string $tranId = null) {
    $settings = HomepageSetting::first();
    $transaction = null;

    if (Schema::hasTable('sslcommerz_transactions') && filled($tranId)) {
        $transaction = SslCommerzTransaction::query()
            ->where('tran_id', trim($tranId))
            ->first();
    }

    $status = strtolower(trim((string) ($transaction->status ?? 'unknown')));
    $statusLabel = match ($status) {
        'approved' => 'Success',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        default => 'Status Update',
    };

    $message = session('success')
        ?? session('error')
        ?? session('warning')
        ?? match ($status) {
            'approved' => 'SSLCommerz payment verified and balance added successfully.',
            'failed' => ($transaction->failure_reason ?: 'SSLCommerz payment failed. Please try again.'),
            'cancelled' => ($transaction->failure_reason ?: 'SSLCommerz payment was cancelled.'),
            default => 'We received your SSLCommerz payment response. Please log in to review the latest status.',
        };

    return view('sslcommerz-status', compact('settings', 'transaction', 'status', 'statusLabel', 'message'));
})->name('user.add.balance.sslcommerz.status');

Route::match(['GET', 'POST'], '/add-balance/sslcommerz/success', function (Request $request) use ($resolveSslCommerzTransaction, $settleSslCommerzTransaction, $sslCommerzRedirectResponse) {
    $transaction = $resolveSslCommerzTransaction($request);

    if (! $transaction) {
        return $sslCommerzRedirectResponse(null, 'error', 'Unable to locate the SSLCommerz transaction.');
    }

    $result = $settleSslCommerzTransaction($transaction, $request->all());

    return $sslCommerzRedirectResponse(
        $result['transaction'] ?? $transaction,
        $result['ok'] ? 'success' : 'error',
        $result['message'] ?? 'Unable to process SSLCommerz payment.',
    );
})->name('user.add.balance.sslcommerz.success');

Route::match(['GET', 'POST'], '/add-balance/sslcommerz/fail', function (Request $request) use ($resolveSslCommerzTransaction, $sslCommerzRedirectResponse) {
    $transaction = $resolveSslCommerzTransaction($request);

    if (! $transaction) {
        return $sslCommerzRedirectResponse(null, 'error', 'SSLCommerz payment failed, but the transaction could not be found.');
    }

    if ($transaction->credited_at === null) {
        $transaction->update([
            'status' => 'failed',
            'gateway_status' => strtolower(trim((string) ($request->input('status') ?: 'failed'))),
            'callback_payload' => array_merge($transaction->callback_payload ?? [], $request->all()),
            'failure_reason' => trim((string) ($request->input('failedreason') ?: 'SSLCommerz payment failed.')),
        ]);
    }

    return $sslCommerzRedirectResponse($transaction->fresh(['user']), 'error', 'SSLCommerz payment failed. Please try again.');
})->name('user.add.balance.sslcommerz.fail');

Route::match(['GET', 'POST'], '/add-balance/sslcommerz/cancel', function (Request $request) use ($resolveSslCommerzTransaction, $sslCommerzRedirectResponse) {
    $transaction = $resolveSslCommerzTransaction($request);

    if (! $transaction) {
        return $sslCommerzRedirectResponse(null, 'error', 'SSLCommerz payment was cancelled.');
    }

    if ($transaction->credited_at === null) {
        $transaction->update([
            'status' => 'cancelled',
            'gateway_status' => strtolower(trim((string) ($request->input('status') ?: 'cancelled'))),
            'callback_payload' => array_merge($transaction->callback_payload ?? [], $request->all()),
            'failure_reason' => trim((string) ($request->input('failedreason') ?: 'SSLCommerz payment was cancelled by the user.')),
        ]);
    }

    return $sslCommerzRedirectResponse($transaction->fresh(['user']), 'error', 'SSLCommerz payment was cancelled.');
})->name('user.add.balance.sslcommerz.cancel');

Route::match(['GET', 'POST'], '/add-balance/sslcommerz/ipn', function (Request $request) use ($resolveSslCommerzTransaction, $settleSslCommerzTransaction) {
    $transaction = $resolveSslCommerzTransaction($request);

    if (! $transaction) {
        return response('Transaction not found.', 404);
    }

    $result = $settleSslCommerzTransaction($transaction, $request->all());

    return response($result['ok'] ? 'OK' : ($result['message'] ?? 'Validation failed.'), $result['ok'] ? 200 : 422);
})->name('user.add.balance.sslcommerz.ipn');

Route::get('/flexiload', function (Request $request) use ($normalizeFlexiOperatorName, $flexiOperatorPrefixes, $resolveFlexiOperatorFromMobile) {
    $settings = \App\Models\HomepageSetting::first();
    $operators = app(HomepageController::class)->selectionOperators();
    $requestedOperator = trim((string) old('operator', $request->query('operator', '')));
    $oldNumber = trim((string) old('number', ''));

    $autoDetectedOperator = $oldNumber !== ''
        ? $resolveFlexiOperatorFromMobile($oldNumber, $operators)
        : null;

    $selectedOperator = $autoDetectedOperator ?: collect($operators)->first(function (array $operator) use ($requestedOperator) {
        if ($requestedOperator === '') {
            return false;
        }

        return strcasecmp((string) ($operator['route_name'] ?? ''), $requestedOperator) === 0
            || strcasecmp((string) ($operator['name'] ?? ''), $requestedOperator) === 0;
    });

    $flexiRequests = collect();

    if (\Illuminate\Support\Facades\Schema::hasTable('flexi_requests')) {
        $flexiRequests = \App\Models\FlexiRequest::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    $operatorPrefixes = collect($operators)
        ->mapWithKeys(function (array $operator) use ($normalizeFlexiOperatorName, $flexiOperatorPrefixes) {
            $key = $normalizeFlexiOperatorName((string) ($operator['route_name'] ?? $operator['name'] ?? ''));

            return $key !== '' && isset($flexiOperatorPrefixes[$key])
                ? [$key => $flexiOperatorPrefixes[$key]]
                : [];
        })
        ->all();

    return view('user-flexi', compact('settings', 'operators', 'selectedOperator', 'autoDetectedOperator', 'flexiRequests', 'operatorPrefixes'));
})->middleware(['auth', 'prevent.back'])->name('user.flexi');

Route::post('/flexiload', function (Request $request) use ($normalizeFlexiOperatorName, $resolveFlexiOperatorFromMobile, $isRechargeAmountBlocked) {
    $validated = $request->validate([
        'operator' => ['nullable', 'string', 'max:100'],
        'number' => ['required', 'regex:/^01[0-9]{9}$/'],
        'amount' => ['required', 'integer', 'min:10', 'max:1499'],
        'type' => ['required', 'in:Prepaid,Postpaid'],
        'pin' => ['required', 'digits:4'],
    ]);

    if (! \Illuminate\Support\Facades\Schema::hasTable('flexi_requests')) {
        return redirect()
            ->route('user.flexi')
            ->withInput()
            ->with('error', 'Please run the latest flexi request migration first.');
    }

    $operators = app(HomepageController::class)->selectionOperators();
    $operatorsByKey = collect($operators)
        ->mapWithKeys(function (array $operator) use ($normalizeFlexiOperatorName) {
            $key = $normalizeFlexiOperatorName((string) ($operator['route_name'] ?? $operator['name'] ?? ''));
            return $key !== '' ? [$key => $operator] : [];
        })
        ->all();

    $selectedKey = $normalizeFlexiOperatorName($validated['operator'] ?? '');
    $detectedOperator = $resolveFlexiOperatorFromMobile($validated['number'], $operators);
    $finalOperator = $detectedOperator ?: ($selectedKey !== '' ? ($operatorsByKey[$selectedKey] ?? null) : null);

    if (! $finalOperator) {
        return redirect()
            ->route('user.flexi')
            ->withInput()
            ->withErrors(['operator' => 'Please choose a valid operator or enter a supported mobile number.']);
    }

    $user = auth()->user();
    $redirectOperator = $finalOperator['route_name'] ?? ($finalOperator['name'] ?? null);
    $securityRuntime = app(SecurityRuntimeService::class);

    if (! $securityRuntime->isOperatorAllowed($finalOperator['route_name'] ?? ($finalOperator['name'] ?? null))) {
        $message = $securityRuntime->operatorBlockedMessage();

        return redirect()
            ->route('user.flexi', array_filter(['operator' => $redirectOperator]))
            ->withInput($request->except('pin'))
            ->withErrors(['operator' => $message])
            ->with('error', $message);
    }

    if (! ($user->pin ?? null) || ! Hash::check($validated['pin'], $user->pin)) {
        return redirect()
            ->route('user.flexi', array_filter(['operator' => $redirectOperator]))
            ->withInput($request->except('pin'))
            ->withErrors(['pin' => 'Invalid PIN.']);
    }

    $amount = (int) $validated['amount'];

    if ($isRechargeAmountBlocked('Flexiload', $finalOperator['short_code'] ?? ($finalOperator['name'] ?? null), $amount)) {
        return redirect()
            ->route('user.flexi', array_filter(['operator' => $redirectOperator]))
            ->withInput($request->except('pin'))
            ->withErrors(['amount' => 'This recharge amount is blocked.']);
    }

    if ((float) ($user->main_bal ?? 0) < $amount) {
        return redirect()
            ->route('user.flexi', array_filter(['operator' => $redirectOperator]))
            ->withInput($request->except('pin'))
            ->withErrors(['amount' => 'Insufficient main balance.']);
    }

    if ($securityRuntime->hasRecentRequest('flexi_requests', (int) $user->id)) {
        $message = $securityRuntime->requestIntervalMessage();

        return redirect()
            ->route('user.flexi', array_filter(['operator' => $redirectOperator]))
            ->withInput($request->except('pin'))
            ->withErrors(['request' => $message])
            ->with('error', $message);
    }

    \Illuminate\Support\Facades\DB::transaction(function () use ($user, $validated, $finalOperator, $amount) {
        \App\Models\FlexiRequest::create([
            'user_id' => $user->id,
            'operator' => $finalOperator['name'] ?? ($validated['operator'] ?? 'Unknown'),
            'mobile' => $validated['number'],
            'amount' => $amount,
            'cost' => $amount,
            'type' => $validated['type'],
            'trnx_id' => null,
            'status' => 'pending',
        ]);

        $user->main_bal = (float) ($user->main_bal ?? 0) - $amount;
        $user->save();
    });

    app(FirebasePushNotificationService::class)->sendToAdmins(
        'New Flexiload Request',
        $user->name . ' requested ' . $validated['type'] . ' flexiload of ' . $amount . ' for ' . $validated['number'] . '.',
        route('admin.pending.drive.requests'),
    );

    return redirect()
        ->route('user.flexi', array_filter(['operator' => $redirectOperator]))
        ->with('success', 'Flexiload request sent successfully.');
})->middleware(['auth', 'prevent.back'])->name('user.flexi.store');

Route::get('/drive-offers', function () {
    $settings = \App\Models\HomepageSetting::first();
    $operators = app(HomepageController::class)->selectionOperators();
    return view('user-drive', compact('settings', 'operators'));
})->middleware(['auth', 'prevent.back', 'permission:drive'])->name('user.drive');

Route::get('/internet-packs', function () {
    $settings = \App\Models\HomepageSetting::first();
    $operators = app(HomepageController::class)->selectionOperators();
    return view('user-internet', compact('settings', 'operators'));
})->middleware(['auth', 'prevent.back', 'permission:internet'])->name('user.internet');

Route::get('/internet-packs/{operator}', function ($operator) {
    $settings = \App\Models\HomepageSetting::first();
    $packages = \App\Models\RegularPackage::where('operator', $operator)
        ->where('status', 'active')
        ->get();
    return view('user-internet-packages', compact('settings', 'operator', 'packages'));
})->middleware(['auth', 'prevent.back', 'permission:internet'])->name('user.internet.packages');

Route::get('/internet-packs/{operator}/buy/{package}', function ($operator, $package) {
    $settings = \App\Models\HomepageSetting::first();
    $package = \App\Models\RegularPackage::findOrFail($package);
    return view('user-internet-buy', compact('settings', 'operator', 'package'));
})->middleware(['auth', 'prevent.back', 'permission:internet'])->name('user.internet.buy');

Route::get('/internet-packs/{operator}/confirm/{package}', function ($operator, $package) {
    $settings = \App\Models\HomepageSetting::first();
    $package = \App\Models\RegularPackage::findOrFail($package);
    $mobile = request('mobile');
    $pin = request('pin');
    return view('user-internet-confirm', compact('settings', 'operator', 'package', 'mobile', 'pin'));
})->middleware(['auth', 'prevent.back', 'permission:internet'])->name('user.internet.confirm');

Route::post('/internet-packs/{operator}/buy/{package}', function (Request $request, $operator, $package) use ($isRechargeAmountBlocked) {
    $packageData = \App\Models\RegularPackage::findOrFail($package);
    $securityRuntime = app(SecurityRuntimeService::class);
    $validated = $request->validate([
        'mobile' => ['required', 'regex:/^01[0-9]{9}$/'],
        'pin' => ['required', 'digits:4'],
    ]);

    $mobile = $validated['mobile'];
    $amount = round((float) ($packageData->price - $packageData->commission), 2);

    $operatorKey = strtolower(preg_replace('/[^a-z]/i', '', $operator));
    $prefixesByOperator = [
        'grameenphone' => ['017', '013'],
        'gp' => ['017', '013'],
        'robi' => ['018'],
        'airtel' => ['016'],
        'banglalink' => ['019', '014'],
        'bl' => ['019', '014'],
        'teletalk' => ['015'],
        'tt' => ['015'],
    ];
    $allowedPrefixes = $prefixesByOperator[$operatorKey] ?? [];

    if ($allowedPrefixes !== [] && !in_array(substr($mobile, 0, 3), $allowedPrefixes, true)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid mobile number for selected operator',
        ], 422);
    }

    if (! $securityRuntime->isOperatorAllowed($packageData->operator ?? $operator)) {
        return response()->json([
            'success' => false,
            'message' => $securityRuntime->operatorBlockedMessage(),
        ], 422);
    }

    if ($isRechargeAmountBlocked('InternetPack', $packageData->operator ?? $operator, $amount)) {
        return response()->json([
            'success' => false,
            'message' => 'This recharge amount is blocked.',
        ], 422);
    }

    $user = auth()->user();

    if (!($user->pin ?? null) || !Hash::check($validated['pin'], $user->pin)) {
        return response()->json(['success' => false, 'message' => 'Invalid PIN'], 422);
    }

    if (($user->main_bal ?? 0) < $amount) {
        return response()->json(['success' => false, 'message' => 'Insufficient main balance'], 422);
    }

    if ($securityRuntime->hasRecentRequest('regular_requests', (int) $user->id)) {
        return response()->json([
            'success' => false,
            'message' => $securityRuntime->requestIntervalMessage(),
        ], 422);
    }

    \App\Models\RegularRequest::create([
        'user_id' => $user->id,
        'package_id' => $package,
        'operator' => $operator,
        'mobile' => $mobile,
        'amount' => $amount,
        'status' => 'pending',
    ]);

    $user->main_bal -= $amount;
    $user->save();

    app(FirebasePushNotificationService::class)->sendToAdmins(
        'New Internet Pack Request',
        $user->name . ' requested ' . $packageData->name . ' for ' . $mobile . '.',
        route('admin.pending.drive.requests'),
    );

    return response()->json(['success' => true]);
})->middleware(['auth', 'prevent.back', 'permission:internet'])->name('user.internet.purchase');

Route::get('/drive-offers/{operator}', function ($operator) {
    $settings = \App\Models\HomepageSetting::first();
    $packages = \App\Models\DrivePackage::where('operator', $operator)
        ->where('status', 'active')
        ->get();
    return view('user-drive-packages', compact('settings', 'operator', 'packages'));
})->middleware(['auth', 'prevent.back', 'permission:drive'])->name('user.drive.packages');

Route::get('/drive-offers/{operator}/buy/{package}', function ($operator, $package) {
    $settings = \App\Models\HomepageSetting::first();
    $package = \App\Models\DrivePackage::findOrFail($package);
    return view('user-drive-buy', compact('settings', 'operator', 'package'));
})->middleware(['auth', 'prevent.back', 'permission:drive'])->name('user.drive.buy');

Route::get('/drive-offers/{operator}/confirm/{package}', function ($operator, $package) {
    $settings = \App\Models\HomepageSetting::first();
    $package = \App\Models\DrivePackage::findOrFail($package);
    $mobile = request('mobile');
    $pin = request('pin');
    $selectedBalanceType = app(SecurityRuntimeService::class)->driveBalanceType();
    $selectedBalanceLabel = $selectedBalanceType === 'main_bal' ? 'main balance' : 'drive balance';
    $availableBalance = (float) (auth()->user()?->{$selectedBalanceType} ?? 0);

    return view('user-drive-confirm', compact(
        'settings',
        'operator',
        'package',
        'mobile',
        'pin',
        'selectedBalanceType',
        'selectedBalanceLabel',
        'availableBalance',
    ));
})->middleware(['auth', 'prevent.back', 'permission:drive'])->name('user.drive.confirm');

Route::get('/profile', function () {
    $settings = HomepageSetting::firstOrCreate([]);
    $user = Auth::user();

    return view('user-profile', compact('settings', 'user'));
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile');

Route::get('/profile/google-otp', function (Request $request) {
    $settings = HomepageSetting::firstOrCreate([]);
    $user = Auth::user();
    $googleOtpSetupSecret = null;
    $googleOtpOtpAuthUrl = null;
    $googleOtpMaskedSecret = null;

    if ($settings->google_otp_enabled) {
        /** @var GoogleOtpService $googleOtpService */
        $googleOtpService = app(GoogleOtpService::class);

        if ($user->google_otp_enabled && filled($user->google_otp_secret)) {
            $googleOtpSetupSecret = $user->google_otp_secret;
            $googleOtpMaskedSecret = $googleOtpService->maskSecret($user->google_otp_secret);
        } else {
            $googleOtpSetupSecret = (string) $request->session()->get('google_otp_setup_secret', $googleOtpService->generateSecret());
            $request->session()->put('google_otp_setup_secret', $googleOtpSetupSecret);
        }

        $issuer = $settings->google_otp_issuer ?: $settings->company_name ?: config('app.name', 'Codecartel Telecom');
        $googleOtpOtpAuthUrl = $googleOtpService->buildOtpAuthUrl($issuer, $user->email, $googleOtpSetupSecret);
    }

    return view('user-google-otp', compact('settings', 'user', 'googleOtpSetupSecret', 'googleOtpOtpAuthUrl', 'googleOtpMaskedSecret'));
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.google-otp');

Route::get('/profile/api', function () {
    $settings = HomepageSetting::firstOrCreate([]);
    $user = Auth::user();

    if (blank($user->api_key)) {
        $user->forceFill([
            'api_key' => Str::upper(Str::random(48)),
        ])->save();
    }

    $domains = ApiDomain::query()
        ->where('user_id', $user->id)
        ->latest()
        ->get();

    $apiServiceOptions = \App\Models\User::apiServiceOptions();
    $enabledApiServices = $user->enabledApiServices();
    $apiApprovalEnabled = $user->hasApprovedApiAccess();

    return view('user-api-settings', compact('settings', 'user', 'domains', 'apiServiceOptions', 'enabledApiServices', 'apiApprovalEnabled'));
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.api');

Route::post('/profile/api/reset', function () {
    $user = Auth::user();

    $user->forceFill([
        'api_key' => Str::upper(Str::random(48)),
    ])->save();

    return redirect()->route('user.profile.api')->with('success', 'API key reset successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.api.reset');

Route::post('/profile/api/services', function (Request $request) {
    $allowedApiServices = array_keys(\App\Models\User::apiServiceOptions());
    $validated = $request->validate([
        'services' => ['nullable', 'array'],
        'services.*' => ['string', \Illuminate\Validation\Rule::in($allowedApiServices)],
    ]);

    $user = Auth::user();
    $user->forceFill([
        'api_services' => array_values(array_unique($validated['services'] ?? [])),
    ])->save();

    return redirect()->route('user.profile.api')->with('success', 'API service settings updated successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.api.services.update');

Route::post('/profile/api/domains', function (Request $request) {
    $validated = $request->validate([
        'domain' => ['required', 'string', 'max:255', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'],
        'provider' => ['required', 'in:Etross'],
    ]);

    ApiDomain::create([
        'user_id' => Auth::id(),
        'domain' => strtolower(trim($validated['domain'])),
        'provider' => $validated['provider'],
    ]);

    return redirect()->route('user.profile.api')->with('success', 'Domain added successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.api.domains.store');

Route::delete('/profile/api/domains/{domain}', function (int $domain) {
    $deleted = ApiDomain::query()
        ->where('id', $domain)
        ->where('user_id', Auth::id())
        ->delete();

    if (! $deleted) {
        return redirect()->route('user.profile.api')->withErrors([
            'domain' => 'Domain not found or you are not allowed to delete it.',
        ]);
    }

    return redirect()->route('user.profile.api')->with('success', 'Domain deleted successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.api.domains.destroy');

Route::put('/profile', function (Request $request) {
    $user = Auth::user();

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'mobile' => ['nullable', 'regex:/^01[0-9]{9}$/'],
        'nid' => ['nullable', 'string', 'max:20'],
    ]);

    $user->name = $validated['name'];
    $user->mobile = $validated['mobile'] ?? $user->mobile;
    $user->nid = $validated['nid'] ?? $user->nid;
    $user->save();

    return redirect()->route('user.profile')->with('success', 'Profile updated successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.update');

Route::put('/profile/password', function (Request $request) {
    $user = Auth::user();
    $securityRuntime = app(SecurityRuntimeService::class);

    $validated = $request->validate([
        'current_password' => ['required'],
        'new_password' => $securityRuntime->passwordRules(),
    ]);

    if (!Hash::check($validated['current_password'], $user->password)) {
        return back()->withErrors(['current_password' => 'Current password is incorrect.']);
    }

    $user->password = Hash::make($validated['new_password']);
    $user->password_changed_at = now();
    $user->save();

    return redirect()->route('user.profile')->with('success', 'Password updated successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.password');

Route::put('/profile/pin', function (Request $request) {
    $user = Auth::user();

    $validated = $request->validate([
        'current_pin' => ['required', 'digits:4'],
        'new_pin' => ['required', 'digits:4', 'confirmed'],
    ]);

    if (!Hash::check($validated['current_pin'], $user->pin)) {
        return back()->withErrors(['current_pin' => 'Current PIN is incorrect.']);
    }

    $user->pin = Hash::make($validated['new_pin']);
    $user->pin_changed_at = now();
    $user->save();

    return redirect()->route('user.profile')->with('success', 'PIN updated successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.pin');

Route::post('/profile/google-otp/enable', function (Request $request) {
    $settings = HomepageSetting::firstOrCreate([]);

    if (! $settings->google_otp_enabled) {
        return redirect()->route('user.profile.google-otp')->withErrors([
            'otp' => 'Google OTP is currently disabled by admin.',
        ]);
    }

    $validated = $request->validate([
        'otp' => ['required', 'digits:6'],
    ]);

    /** @var GoogleOtpService $googleOtpService */
    $googleOtpService = app(GoogleOtpService::class);
    $user = Auth::user();
    $secret = $user->google_otp_enabled && filled($user->google_otp_secret)
        ? (string) $user->google_otp_secret
        : (string) $request->session()->get('google_otp_setup_secret');

    if (blank($secret)) {
        $secret = $googleOtpService->generateSecret();
        $request->session()->put('google_otp_setup_secret', $secret);
    }

    if (! $googleOtpService->verifyCode($secret, $validated['otp'])) {
        return back()->withErrors([
            'otp' => 'Invalid Google Authenticator OTP.',
        ])->withInput();
    }

    $user->forceFill([
        'google_otp_secret' => $secret,
        'google_otp_enabled' => true,
        'google_otp_confirmed_at' => now(),
    ])->save();

    $request->session()->forget('google_otp_setup_secret');

    return redirect()->route('user.profile.google-otp')->with('success', 'Google Authenticator enabled successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.google-otp.enable');

Route::post('/profile/google-otp/disable', function (Request $request) {
    $validated = $request->validate([
        'disable_pin' => ['required', 'digits:4'],
    ]);

    $user = Auth::user();

    if (! $user->pin || ! Hash::check($validated['disable_pin'], $user->pin)) {
        return back()->withErrors([
            'disable_pin' => 'Current PIN is incorrect.',
        ]);
    }

    $user->forceFill([
        'google_otp_secret' => null,
        'google_otp_enabled' => false,
        'google_otp_confirmed_at' => null,
    ])->save();

    $request->session()->forget('google_otp_setup_secret');

    return redirect()->route('user.profile.google-otp')->with('success', 'Google Authenticator disabled successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.google-otp.disable');

Route::put('/profile/picture', function (Request $request) {
    $user = Auth::user();

    $validated = $request->validate([
        'profile_picture' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
    ]);

    if ($request->hasFile('profile_picture')) {
        $file = $validated['profile_picture'];
        $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Store in public/uploads/profilePictures folder
        $uploadPath = public_path('uploads/profilePictures');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        $file->move($uploadPath, $filename);

        // Delete old picture if exists
        if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
            unlink(public_path($user->profile_picture));
        }

        $user->profile_picture = 'uploads/profilePictures/' . $filename;
        $user->save();
    }

    return redirect()->route('user.profile')->with('success', 'Profile picture updated successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.picture');

Route::delete('/profile/picture', function () {
    $user = Auth::user();

    if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
        unlink(public_path($user->profile_picture));
    }

    $user->profile_picture = null;
    $user->save();

    return redirect()->route('user.profile')->with('success', 'Profile picture removed successfully!');
})->middleware(['auth', 'prevent.back', 'permission:profile'])->name('user.profile.picture.delete');

Route::post('/drive-offers/{operator}/buy/{package}', function (Request $request, $operator, $package) {
    $packageData = \App\Models\DrivePackage::findOrFail($package);
    $securityRuntime = app(SecurityRuntimeService::class);
    $validated = $request->validate([
        'mobile' => ['required', 'regex:/^01[0-9]{9}$/'],
        'pin' => ['required', 'digits:4'],
    ]);

    $mobile = $validated['mobile'];
    $amount = $packageData->price - $packageData->commission;

    $user = auth()->user();
    $balanceType = $securityRuntime->driveBalanceType();
    $balanceLabel = $balanceType === 'main_bal' ? 'main balance' : 'drive balance';

    if (! $securityRuntime->isOperatorAllowed($operator)) {
        return response()->json(['success' => false, 'message' => $securityRuntime->operatorBlockedMessage()], 422);
    }

    if (!($user->pin ?? null) || !Hash::check($validated['pin'], $user->pin)) {
        return response()->json(['success' => false, 'message' => 'Invalid PIN'], 422);
    }

    if (($user->{$balanceType} ?? 0) < $amount) {
        return response()->json(['success' => false, 'message' => 'Insufficient ' . $balanceLabel], 422);
    }

    if ($securityRuntime->hasRecentRequest('drive_requests', (int) $user->id)) {
        return response()->json([
            'success' => false,
            'message' => $securityRuntime->requestIntervalMessage(),
        ], 422);
    }

    // Create drive request
    $driveRequestAttributes = [
        'user_id' => auth()->id(),
        'package_id' => $package,
        'operator' => $operator,
        'mobile' => $mobile,
        'amount' => $amount,
        'status' => 'pending'
    ];

    if (\Illuminate\Support\Facades\Schema::hasColumn('drive_requests', 'balance_type')) {
        $driveRequestAttributes['balance_type'] = $balanceType;
    }

    \App\Models\DriveRequest::create($driveRequestAttributes);

    // Deduct from user's selected balance
    $user->{$balanceType} -= $amount;
    $user->save();

    app(FirebasePushNotificationService::class)->sendToAdmins(
        'New Drive Offer Request',
        $user->name . ' requested ' . $packageData->name . ' for ' . $mobile . '.',
        route('admin.pending.drive.requests'),
    );

    return response()->json(['success' => true]);
})->middleware(['auth', 'prevent.back', 'permission:drive'])->name('user.drive.purchase');

Route::get('/my-drive-history', function () {
    $settings = \App\Models\HomepageSetting::first();
    $driveHistory = \DB::table('drive_history')
        ->where('user_id', auth()->id())
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($item) {
            $item->operator = $item->operator ?? 'Drive';
            $item->mobile = $item->mobile ?? '-';
            $item->status = $item->status ?? 'success';
            $item->description = $item->description ?? 'Drive Recharge';
            return $item;
        });

    $internetHistory = \App\Models\RegularRequest::where('user_id', auth()->id())
        ->whereIn('status', ['approved', 'rejected', 'cancelled'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($item) {
            $status = match ($item->status) {
                'approved' => 'success',
                'cancelled' => 'cancelled',
                default => 'failed',
            };

            $description = match ($item->status) {
                'approved' => 'Internet Pack Recharge',
                'cancelled' => 'Internet Pack Request Cancelled',
                default => 'Internet Pack Request Failed',
            };

            return (object) [
                'operator' => $item->operator ?? 'Internet Pack',
                'mobile' => $item->mobile ?? '-',
                'amount' => $item->amount,
                'status' => $status,
                'description' => $description,
                'created_at' => $item->created_at,
            ];
        });

    $history = $driveHistory
        ->concat($internetHistory)
        ->sortByDesc('created_at')
        ->values();

    return view('user-drive-history', compact('settings', 'history'));
})->middleware(['auth', 'prevent.back', 'permission:drive_history'])->name('user.drive.history');

Route::get('/my-history', function (Request $request) {
    $settings = \App\Models\HomepageSetting::first();
    $user = Auth::user();
    $dateFrom = $request->query('date_from');
    $dateTo = $request->query('date_to');
    $manualHistoryTypes = ['bkash', 'nagad', 'rocket', 'upay'];
    $supportedHistoryTypes = ['all', 'flexi', 'internet', ...$manualHistoryTypes];
    $requestedHistoryType = strtolower(trim((string) $request->query('type', '')));
    $historyType = in_array($requestedHistoryType, $supportedHistoryTypes, true)
        ? $requestedHistoryType
        : 'all';
    $todayDate = now()->toDateString();

    if (empty($dateFrom) && empty($dateTo)) {
        $startDate = now()->startOfDay();
        $endDate = now()->endOfDay();
        $dateFrom = $todayDate;
        $dateTo = $todayDate;
    } else {
        $startDate = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : now()->subYear()->startOfDay();
        $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();
    }

    $driveHistory = \DB::table('drive_history')
        ->where('user_id', auth()->id())
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($item) {
            $item->type = 'drive';
            $item->operator = $item->operator ?? 'Drive';
            $item->mobile = $item->mobile ?? '-';
            $item->status = $item->status ?? 'success';
            $item->description = $item->description ?? 'Drive Recharge';
            return $item;
        });

    $internetHistory = \App\Models\RegularRequest::where('user_id', auth()->id())
        ->whereIn('status', ['approved', 'rejected', 'cancelled'])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($item) {
            $status = match ($item->status) {
                'approved' => 'success',
                'cancelled' => 'cancelled',
                default => 'failed',
            };

            $description = match ($item->status) {
                'approved' => 'Internet Pack Recharge',
                'cancelled' => 'Internet Pack Request Cancelled',
                default => 'Internet Pack Request Failed',
            };

            return (object) [
                'type' => 'internet',
                'operator' => $item->operator ?? 'Internet Pack',
                'mobile' => $item->mobile ?? '-',
                'amount' => $item->amount,
                'status' => $status,
                'description' => $description,
                'created_at' => $item->created_at,
            ];
        });

    $flexiHistory = \Illuminate\Support\Facades\Schema::hasTable('flexi_requests')
        ? \App\Models\FlexiRequest::where('user_id', auth()->id())
        ->whereIn('status', ['approved', 'rejected', 'cancelled'])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($item) {
            $status = 'success';

            if ($item->status === 'rejected') {
                $status = 'failed';
            } elseif ($item->status === 'cancelled') {
                $status = 'cancelled';
            }

            return (object) [
                'type' => 'flexi',
                'operator' => $item->operator ?? 'Flexi',
                'mobile' => $item->mobile ?? '-',
                'amount' => $item->amount,
                'status' => $status,
                'description' => trim(($item->type ?? 'Flexi') . ' Flexiload'),
                'created_at' => $item->created_at,
            ];
        })
        : collect();

    $manualBalanceHistory = Schema::hasTable('balance_add_history')
        ? DB::table('balance_add_history')
        ->where('user_id', auth()->id())
        ->whereIn('type', $manualHistoryTypes)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($item) {
            $type = strtolower(trim((string) ($item->type ?? '')));
            $label = match ($type) {
                'bkash' => 'Bkash',
                'nagad' => 'Nagad',
                'rocket' => 'Rocket',
                'upay' => 'Upay',
                default => 'Balance Add',
            };

            return (object) [
                'type' => $type,
                'operator' => $label,
                'mobile' => '-',
                'amount' => $item->amount,
                'status' => 'success',
                'description' => filled($item->description ?? null) ? $item->description : ($label . ' Balance Add'),
                'created_at' => $item->created_at,
            ];
        })
        : collect();

    $history = (match ($historyType) {
        'flexi' => $flexiHistory,
        'internet' => $internetHistory,
        'bkash' => $manualBalanceHistory->where('type', 'bkash'),
        'nagad' => $manualBalanceHistory->where('type', 'nagad'),
        'rocket' => $manualBalanceHistory->where('type', 'rocket'),
        'upay' => $manualBalanceHistory->where('type', 'upay'),
        default => $driveHistory
            ->concat($internetHistory)
            ->concat($flexiHistory)
            ->concat($manualBalanceHistory),
    })
        ->sortByDesc('created_at')
        ->values();

    return view('user-all-history', compact('settings', 'user', 'history', 'dateFrom', 'dateTo', 'historyType'));
})->middleware(['auth', 'prevent.back', 'permission:all_history'])->name('user.all.history');

Route::get('/my-pending-requests', function () {
    $settings = \App\Models\HomepageSetting::first();
    $user = Auth::user();
    $pendingDriveRequests = \App\Models\DriveRequest::where('user_id', auth()->id())
        ->where('status', 'pending')
        ->with('package')
        ->get()
        ->map(function ($item) {
            $item->request_type = 'Drive';
            return $item;
        });

    $pendingRegularRequests = \App\Models\RegularRequest::where('user_id', auth()->id())
        ->where('status', 'pending')
        ->with('package')
        ->get();

    $pendingRegularRequests = $pendingRegularRequests->map(function ($item) {
        $item->request_type = 'Internet';
        return $item;
    });

    $pendingFlexiRequests = collect();

    if (\Illuminate\Support\Facades\Schema::hasTable('flexi_requests')) {
        $pendingFlexiRequests = \App\Models\FlexiRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->get()
            ->map(function ($item) {
                $item->request_type = 'Flexi';
                return $item;
            });
    }

    $pendingManualPaymentRequests = collect();

    if (\Illuminate\Support\Facades\Schema::hasTable('manual_payment_requests') && filled(auth()->id())) {
        $pendingManualPaymentRequests = ManualPaymentRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->get()
            ->map(function ($item) {
                $item->request_type = 'mobile banking';
                $item->request_category = 'manual_payment';
                $item->operator = $item->method;
                $item->mobile = $item->sender_number;
                $item->type = $item->note ?: $item->transaction_id;
                return $item;
            });
    }

    $pendingRequests = $pendingDriveRequests
        ->concat($pendingRegularRequests)
        ->concat($pendingFlexiRequests)
        ->concat($pendingManualPaymentRequests)
        ->sortByDesc('created_at')
        ->values();

    return view('dashboard', ['user' => $user, 'settings' => $settings, 'pendingRequests' => $pendingRequests, 'showPendingPage' => true]);
})->middleware(['auth', 'prevent.back', 'permission:pending_requests'])->name('user.pending.requests');
