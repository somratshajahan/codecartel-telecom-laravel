<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ApiDomain;
use App\Models\HomepageSetting;
use App\Models\ManualPaymentRequest;
use App\Models\RechargeBlockList;
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


$normalizeFlexiOperatorName = static function (?string $value): string {
    return strtolower(preg_replace('/[^a-z]/i', '', (string) $value));
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

Route::get('/add-balance', function () {
    $settings = \App\Models\HomepageSetting::first();
    $branding = \App\Models\Branding::first();
    $user = Auth::user();

    $manualMethods = collect([
        ['name' => 'Bkash', 'number' => optional($branding)->bkash, 'color' => 'bg-pink-500'],
        ['name' => 'Rocket', 'number' => optional($branding)->rocket, 'color' => 'bg-violet-600'],
        ['name' => 'Nagad', 'number' => optional($branding)->nagad, 'color' => 'bg-orange-500'],
        ['name' => 'Upay', 'number' => optional($branding)->upay, 'color' => 'bg-emerald-600'],
    ])->filter(fn(array $method) => filled($method['number']))->values();

    $recentRequests = collect();

    if (\Illuminate\Support\Facades\Schema::hasTable('manual_payment_requests') && filled($user?->getAuthIdentifier())) {
        $recentRequests = ManualPaymentRequest::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->latest()
            ->limit(10)
            ->get();
    }

    return view('user-add-balance', compact('settings', 'branding', 'user', 'manualMethods', 'recentRequests'));
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.add.balance');

Route::post('/add-balance', function (Request $request) {
    if (! \Illuminate\Support\Facades\Schema::hasTable('manual_payment_requests')) {
        return redirect()->route('user.add.balance')->with('error', 'Manual payment request system is not ready yet.');
    }

    $branding = \App\Models\Branding::first();
    $availableMethods = collect([
        'Bkash' => optional($branding)->bkash,
        'Rocket' => optional($branding)->rocket,
        'Nagad' => optional($branding)->nagad,
        'Upay' => optional($branding)->upay,
    ])->filter(fn($number) => filled($number))->keys()->values()->all();

    if (empty($availableMethods)) {
        return redirect()->route('user.add.balance')->with('error', 'No manual payment method is available right now.');
    }

    $validated = $request->validate([
        'method' => ['required', \Illuminate\Validation\Rule::in($availableMethods)],
        'sender_number' => ['required', 'regex:/^01[0-9]{9}$/'],
        'transaction_id' => ['required', 'string', 'max:255', 'unique:manual_payment_requests,transaction_id'],
        'amount' => ['required', 'numeric', 'min:1'],
        'note' => ['nullable', 'string', 'max:1000'],
    ]);

    $securityRuntime = app(SecurityRuntimeService::class);

    if ($securityRuntime->hasRecentRequest('manual_payment_requests', (int) auth()->id())) {
        $message = $securityRuntime->requestIntervalMessage();

        return redirect()
            ->route('user.add.balance')
            ->withInput()
            ->withErrors(['request' => $message])
            ->with('error', $message);
    }

    ManualPaymentRequest::create([
        'user_id' => auth()->id(),
        'method' => trim((string) $validated['method']),
        'sender_number' => trim((string) $validated['sender_number']),
        'transaction_id' => trim((string) $validated['transaction_id']),
        'amount' => $validated['amount'],
        'note' => filled($validated['note'] ?? null) ? trim((string) $validated['note']) : null,
        'status' => 'pending',
    ]);

    return redirect()->route('user.add.balance')->with('success', 'Manual payment request submitted successfully. Please wait for admin approval.');
})->middleware(['auth', 'prevent.back', 'permission:add_balance'])->name('user.add.balance.submit');

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
    $branding = \App\Models\Branding::query()->first();
    $selectedBalanceType = (($branding->drive_balance ?? 'on') === 'off') ? 'main_bal' : 'drive_bal';
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
    $branding = \App\Models\Branding::query()->first();
    $balanceType = (($branding->drive_balance ?? 'on') === 'off') ? 'main_bal' : 'drive_bal';
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
    $dateFrom = $request->query('date_from');
    $dateTo = $request->query('date_to');
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

    $history = $driveHistory
        ->concat($internetHistory)
        ->concat($flexiHistory)
        ->sortByDesc('created_at')
        ->values();

    return view('user-all-history', compact('settings', 'history', 'dateFrom', 'dateTo'));
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
                $item->request_type = 'Balance Add';
                $item->request_category = 'manual_payment';
                $item->operator = $item->method;
                $item->mobile = $item->sender_number;
                $item->type = $item->transaction_id;
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
