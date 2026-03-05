<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\AdminController;
Route::get('/', [HomepageController::class, 'index'])->name('homepage');

// Admin routes


Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
Route::post('/complaints/store', [ComplaintController::class, 'store'])->name('complaints.store');

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
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.resellers');

Route::get('/admin/all-resellers', [AdminController::class, 'allResellers'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.all.resellers');

Route::get('/admin/add-balance/{userId}', [AdminController::class, 'addBalance'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.add.balance');

Route::post('/admin/add-balance/{userId}', [AdminController::class, 'storeBalance'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.store.balance');

Route::get('/admin/return-balance/{userId}', [AdminController::class, 'returnBalance'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.return.balance');

Route::post('/admin/return-balance/{userId}', [AdminController::class, 'storeReturnBalance'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.store.return.balance');

Route::post('/admin/resellers/{user}/toggle', [AdminController::class, 'toggleStatus'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.resellers.toggle');

Route::post('/admin/users/store', [AdminController::class, 'storeUser'])
    ->middleware(['auth', 'admin', 'prevent.back'])
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

Route::get('/admin/drive-history', [AdminController::class, 'driveHistory'])
    ->middleware(['auth', 'admin', 'prevent.back'])
    ->name('admin.drive.history');
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

Route::get('/login', [AuthPageController::class, 'login'])->name('login');
Route::post('/login', [AuthPageController::class, 'handleLogin']);

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

    $pendingRequests = $pendingDriveRequests
        ->concat($pendingRegularRequests)
        ->sortByDesc('created_at')
        ->values();
    
    // Get last received balance history for the logged-in user
    $lastReceived = \DB::table('balance_add_history')
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    return view('dashboard', ['user' => $user, 'settings' => $settings, 'pendingRequests' => $pendingRequests, 'lastReceived' => $lastReceived]); 
})->middleware(['auth', 'prevent.back'])->name('dashboard');

Route::get('/drive-offers', function () {
    $settings = \App\Models\HomepageSetting::first();
    $operators = [
        ['name' => 'Robi', 'code' => 'RB', 'color' => 'bg-orange-500'],
        ['name' => 'GrameenPhone', 'code' => 'GP', 'color' => 'bg-blue-500'],
        ['name' => 'Teletalk', 'code' => 'TT', 'color' => 'bg-red-500'],
        ['name' => 'Banglalink', 'code' => 'BL', 'color' => 'bg-green-500'],
        ['name' => 'Airtel', 'code' => 'AT', 'color' => 'bg-red-600']
    ];
    return view('user-drive', compact('settings', 'operators'));
})->middleware(['auth', 'prevent.back'])->name('user.drive');

Route::get('/internet-packs', function () {
    $settings = \App\Models\HomepageSetting::first();
    $operators = [
        ['name' => 'Robi', 'code' => 'RB', 'color' => 'bg-orange-500'],
        ['name' => 'GrameenPhone', 'code' => 'GP', 'color' => 'bg-blue-500'],
        ['name' => 'Teletalk', 'code' => 'TT', 'color' => 'bg-red-500'],
        ['name' => 'Banglalink', 'code' => 'BL', 'color' => 'bg-green-500'],
        ['name' => 'Airtel', 'code' => 'AT', 'color' => 'bg-red-600']
    ];
    return view('user-internet', compact('settings', 'operators'));
})->middleware(['auth', 'prevent.back'])->name('user.internet');

Route::get('/internet-packs/{operator}', function ($operator) {
    $settings = \App\Models\HomepageSetting::first();
    $packages = \App\Models\RegularPackage::where('operator', $operator)
        ->where('status', 'active')
        ->get();
    return view('user-internet-packages', compact('settings', 'operator', 'packages'));
})->middleware(['auth', 'prevent.back'])->name('user.internet.packages');

Route::get('/internet-packs/{operator}/buy/{package}', function ($operator, $package) {
    $settings = \App\Models\HomepageSetting::first();
    $package = \App\Models\RegularPackage::findOrFail($package);
    return view('user-internet-buy', compact('settings', 'operator', 'package'));
})->middleware(['auth', 'prevent.back'])->name('user.internet.buy');

Route::get('/internet-packs/{operator}/confirm/{package}', function ($operator, $package) {
    $settings = \App\Models\HomepageSetting::first();
    $package = \App\Models\RegularPackage::findOrFail($package);
    $mobile = request('mobile');
    $pin = request('pin');
    return view('user-internet-confirm', compact('settings', 'operator', 'package', 'mobile', 'pin'));
})->middleware(['auth', 'prevent.back'])->name('user.internet.confirm');

Route::post('/internet-packs/{operator}/buy/{package}', function ($operator, $package) {
    $packageData = \App\Models\RegularPackage::findOrFail($package);
    $mobile = request('mobile');
    $amount = $packageData->price - $packageData->commission;

    $user = auth()->user();
    if (($user->main_bal ?? 0) < $amount) {
        return response()->json(['success' => false, 'message' => 'Insufficient main balance'], 422);
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

    return response()->json(['success' => true]);
})->middleware(['auth', 'prevent.back'])->name('user.internet.purchase');

Route::get('/drive-offers/{operator}', function ($operator) {
    $settings = \App\Models\HomepageSetting::first();
    $packages = \App\Models\DrivePackage::where('operator', $operator)
        ->where('status', 'active')
        ->get();
    return view('user-drive-packages', compact('settings', 'operator', 'packages'));
})->middleware(['auth', 'prevent.back'])->name('user.drive.packages');

Route::get('/drive-offers/{operator}/buy/{package}', function ($operator, $package) {
    $settings = \App\Models\HomepageSetting::first();
    $package = \App\Models\DrivePackage::findOrFail($package);
    return view('user-drive-buy', compact('settings', 'operator', 'package'));
})->middleware(['auth', 'prevent.back'])->name('user.drive.buy');

Route::get('/drive-offers/{operator}/confirm/{package}', function ($operator, $package) {
    $settings = \App\Models\HomepageSetting::first();
    $package = \App\Models\DrivePackage::findOrFail($package);
    $mobile = request('mobile');
    $pin = request('pin');
    return view('user-drive-confirm', compact('settings', 'operator', 'package', 'mobile', 'pin'));
})->middleware(['auth', 'prevent.back'])->name('user.drive.confirm');

Route::get('/profile', function () {
    $settings = \App\Models\HomepageSetting::first();
    $user = Auth::user();
    return view('user-profile', compact('settings', 'user'));
})->middleware(['auth', 'prevent.back'])->name('user.profile');

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
})->middleware(['auth', 'prevent.back'])->name('user.profile.update');

Route::put('/profile/password', function (Request $request) {
    $user = Auth::user();
    
    $validated = $request->validate([
        'current_password' => ['required'],
        'new_password' => ['required', 'min:6', 'confirmed'],
    ]);

    if (!Hash::check($validated['current_password'], $user->password)) {
        return back()->withErrors(['current_password' => 'Current password is incorrect.']);
    }

    $user->password = Hash::make($validated['new_password']);
    $user->save();

    return redirect()->route('user.profile')->with('success', 'Password updated successfully!');
})->middleware(['auth', 'prevent.back'])->name('user.profile.password');

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
    $user->save();

    return redirect()->route('user.profile')->with('success', 'PIN updated successfully!');
})->middleware(['auth', 'prevent.back'])->name('user.profile.pin');

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
})->middleware(['auth', 'prevent.back'])->name('user.profile.picture');

Route::delete('/profile/picture', function () {
    $user = Auth::user();
    
    if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
        unlink(public_path($user->profile_picture));
    }
    
    $user->profile_picture = null;
    $user->save();

    return redirect()->route('user.profile')->with('success', 'Profile picture removed successfully!');
})->middleware(['auth', 'prevent.back'])->name('user.profile.picture.delete');

Route::post('/drive-offers/{operator}/buy/{package}', function ($operator, $package) {
    $packageData = \App\Models\DrivePackage::findOrFail($package);
    $mobile = request('mobile');
    $amount = $packageData->price - $packageData->commission;
    
    // Create drive request
    \App\Models\DriveRequest::create([
        'user_id' => auth()->id(),
        'package_id' => $package,
        'operator' => $operator,
        'mobile' => $mobile,
        'amount' => $amount,
        'status' => 'pending'
    ]);
    
    // Deduct from user's drive balance
    $user = auth()->user();
    $user->drive_bal -= $amount;
    $user->save();
    
    return response()->json(['success' => true]);
})->middleware(['auth', 'prevent.back'])->name('user.drive.purchase');

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
        ->whereIn('status', ['approved', 'rejected'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($item) {
            return (object) [
                'operator' => $item->operator ?? 'Internet Pack',
                'mobile' => $item->mobile ?? '-',
                'amount' => $item->amount,
                'status' => $item->status === 'approved' ? 'success' : 'failed',
                'description' => $item->status === 'approved' ? 'Internet Pack Recharge' : 'Internet Pack Request Failed',
                'created_at' => $item->created_at,
            ];
        });

    $history = $driveHistory
        ->concat($internetHistory)
        ->sortByDesc('created_at')
        ->values();

    return view('user-drive-history', compact('settings', 'history'));
})->middleware(['auth', 'prevent.back'])->name('user.drive.history');

Route::get('/my-history', function () {
    $settings = \App\Models\HomepageSetting::first();
    $user = auth()->user();
    
    $driveHistory = \DB::table('drive_history')
        ->where('user_id', auth()->id())
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
        ->whereIn('status', ['approved', 'rejected'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($item) {
            return (object) [
                'type' => 'internet',
                'operator' => $item->operator ?? 'Internet Pack',
                'mobile' => $item->mobile ?? '-',
                'amount' => $item->amount,
                'status' => $item->status === 'approved' ? 'success' : 'failed',
                'description' => $item->status === 'approved' ? 'Internet Pack Recharge' : 'Internet Pack Request Failed',
                'created_at' => $item->created_at,
            ];
        });

    $history = $driveHistory
        ->concat($internetHistory)
        ->sortByDesc('created_at')
        ->values();

    return view('user-all-history', compact('settings', 'history'));
})->middleware(['auth', 'prevent.back'])->name('user.all.history');

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

    $pendingRequests = $pendingDriveRequests
        ->concat($pendingRegularRequests)
        ->sortByDesc('created_at')
        ->values();

    return view('dashboard', ['user' => $user, 'settings' => $settings, 'pendingRequests' => $pendingRequests, 'showPendingPage' => true]);
})->middleware(['auth', 'prevent.back'])->name('user.pending.requests');