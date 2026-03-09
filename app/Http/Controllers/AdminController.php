<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\HomepageSetting;
use App\Models\ManualPaymentRequest;
use App\Models\Operator;
use App\Models\RechargeBlockList;
use App\Models\DrivePackage;
use App\Models\RegularPackage;
use App\Models\ServiceModule;
use App\Services\FirebasePushNotificationService;
use App\Services\GoogleOtpService;
use App\Services\SecurityRuntimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function dashboard()
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();

        // Calculate total amount from all users
        $totalAmount = User::where('is_admin', false)->sum('main_bal');
        $totalUsers = User::where('is_admin', false)->count();

        // Pending requests count
        $pendingCount = $this->pendingRequestCount();

        // Recharge history comparison (today vs yesterday)
        $today = \DB::table('recharge_history')
            ->whereDate('created_at', today())
            ->sum('amount');
        $yesterday = \DB::table('recharge_history')
            ->whereDate('created_at', today()->subDay())
            ->sum('amount');

        // Balance add history comparison (today vs yesterday)
        $balanceToday = \DB::table('balance_add_history')
            ->whereDate('created_at', today())
            ->sum('amount');
        $balanceYesterday = \DB::table('balance_add_history')
            ->whereDate('created_at', today()->subDay())
            ->sum('amount');

        // Operator sales percentage (today)
        $operatorSales = \DB::table('recharge_history')
            ->select('type', \DB::raw('SUM(amount) as total'))
            ->whereDate('created_at', today())
            ->whereIn('type', ['Grameenphone', 'Robi', 'Banglalink', 'Airtel', 'Teletalk'])
            ->groupBy('type')
            ->get();

        // Mobile banking sales (today)
        $bankingSales = \DB::table('recharge_history')
            ->select('type', \DB::raw('SUM(amount) as total'))
            ->whereDate('created_at', today())
            ->whereIn('type', ['Bkash', 'Nagad', 'Rocket', 'Upay'])
            ->groupBy('type')
            ->get();

        return view('admin', compact('settings', 'operators', 'totalAmount', 'totalUsers', 'today', 'yesterday', 'balanceToday', 'balanceYesterday', 'operatorSales', 'bankingSales', 'pendingCount'));
    }

    protected function balanceTypeLabel(string $balanceType): string
    {
        return match ($balanceType) {
            'main_bal' => 'Main Balance',
            'drive_bal' => 'Drive Balance',
            'bank_bal' => 'Bank Balance',
            default => 'Balance',
        };
    }

    protected function notifyUser(?User $user, string $title, string $body, ?string $link = null): void
    {
        app(FirebasePushNotificationService::class)->sendToUser($user, $title, $body, $link);
    }

    protected function notifyAllUsers(string $title, string $body, ?string $link = null): void
    {
        app(FirebasePushNotificationService::class)->sendToAllUsers($title, $body, $link);
    }

    protected function hasValidAdminPin(string $pin): bool
    {
        $admin = auth()->user();

        return $admin !== null
            && filled($admin->pin)
            && Hash::check($pin, $admin->pin);
    }

    protected function syncRoutedSettlement(object $requestModel, string $defaultRequestType, string $status, ?string $description = null, ?string $trnxId = null): array
    {
        if (! (bool) ($requestModel->is_routed ?? false)) {
            return ['ok' => true];
        }

        $callbackUrl = trim((string) ($requestModel->source_callback_url ?? ''));
        $sourceApiKey = trim((string) ($requestModel->source_api_key ?? ''));
        $sourceRequestId = trim((string) ($requestModel->source_request_id ?? ''));

        if ($callbackUrl === '' || $sourceApiKey === '' || ! preg_match('/^[0-9]+$/', $sourceRequestId)) {
            return [
                'ok' => false,
                'message' => 'Unable to sync routed request with source system.',
            ];
        }

        $requestType = trim((string) ($requestModel->source_request_type ?? ''));

        if (! in_array($requestType, ['recharge', 'drive', 'internet'], true)) {
            $requestType = $defaultRequestType;
        }

        $clientDomain = trim((string) ($requestModel->source_client_domain ?? ''));
        $payload = [
            'source_request_id' => (int) $sourceRequestId,
            'request_type' => $requestType,
            'status' => $status,
            'remote_request_id' => $this->resolveRoutedRemoteRequestId($requestModel),
            'description' => filled($description) ? trim((string) $description) : null,
            'trnx_id' => filled($trnxId) ? trim((string) $trnxId) : null,
        ];

        if ($clientDomain !== '') {
            $payload['domain'] = $clientDomain;
        }

        $headers = [
            'X-API-KEY' => $sourceApiKey,
        ];

        if ($clientDomain !== '') {
            $headers['X-Client-Domain'] = $clientDomain;
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(15)
                ->withHeaders($headers)
                ->post($callbackUrl, $payload);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Unable to sync routed request with source system.',
            ];
        }

        $responseBody = $response->json();

        if (! $response->successful() || (($responseBody['status'] ?? null) === 'error')) {
            return [
                'ok' => false,
                'message' => 'Unable to sync routed request with source system.',
            ];
        }

        return ['ok' => true];
    }

    protected function resolveRoutedRemoteRequestId(object $requestModel): ?string
    {
        $remoteRequestId = trim((string) ($requestModel->remote_request_id ?? ''));

        if ($remoteRequestId !== '') {
            return $remoteRequestId;
        }

        $localRequestId = trim((string) ($requestModel->id ?? ''));

        return $localRequestId !== '' ? $localRequestId : null;
    }

    protected function pendingRequestCount(): int
    {
        $drivePending = Schema::hasTable('drive_requests')
            ? \App\Models\DriveRequest::where('status', 'pending')->count()
            : 0;

        $regularPending = Schema::hasTable('regular_requests')
            ? \App\Models\RegularRequest::where('status', 'pending')->count()
            : 0;

        $flexiPending = Schema::hasTable('flexi_requests')
            ? \App\Models\FlexiRequest::where('status', 'pending')->count()
            : 0;

        $manualPaymentPending = Schema::hasTable('manual_payment_requests')
            ? ManualPaymentRequest::where('status', 'pending')->count()
            : 0;

        return $drivePending + $regularPending + $flexiPending + $manualPaymentPending;
    }

    protected function storeBalanceHistory(User $user, $amount, string $type, ?string $description = null): void
    {
        $payload = [
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('balance_add_history', 'description')) {
            $payload['description'] = $description;
        }

        \DB::table('balance_add_history')->insert($payload);
    }

    protected function balanceNotificationMessage(string $action, string $amount, string $balanceTypeName, ?string $description = null): string
    {
        $message = $amount . ' Tk ' . $action . ' your ' . $balanceTypeName . '.';

        if (filled($description)) {
            $message .= ' Description: ' . $description;
        }

        return $message;
    }

    protected function filterPermissionKeys(?array $permissions, array $allowedKeys): array
    {
        return collect($permissions ?? [])
            ->filter(fn($permission) => filled($permission))
            ->map(fn($permission) => (string) $permission)
            ->filter(fn($permission) => in_array($permission, $allowedKeys, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Show add balance page.
     */
    public function addBalance($userId)
    {
        $settings = HomepageSetting::first();
        $user = User::findOrFail($userId);
        return view('admin.add-balance', compact('settings', 'user'));
    }

    /**
     * Store balance.
     */
    public function storeBalance(Request $request, $userId)
    {
        $validated = $request->validate([
            'balance_type' => ['required', 'in:main_bal,drive_bal,bank_bal'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'pin' => ['required', 'digits:4'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()
                ->withErrors(['pin' => 'Invalid admin PIN.'])
                ->withInput();
        }

        $user = User::findOrFail($userId);
        $balanceType = $validated['balance_type'];
        $description = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : null;

        $user->$balanceType += $validated['amount'];
        $user->save();

        // Refresh the user to ensure database changes are loaded
        $user->refresh();

        // Record balance addition in history
        $balanceTypeName = $this->balanceTypeLabel($balanceType);

        $this->storeBalanceHistory($user, $validated['amount'], $balanceTypeName, $description);

        $this->notifyUser(
            $user,
            'Balance Added',
            $this->balanceNotificationMessage(
                'added to',
                number_format((float) $validated['amount'], 2),
                $balanceTypeName,
                $description,
            ),
            route('dashboard'),
        );

        return redirect()->route('admin.resellers')->with('success', 'Balance added successfully!');
    }

    /**
     * Show return balance page.
     */
    public function returnBalance($userId)
    {
        $settings = HomepageSetting::first();
        $user = User::findOrFail($userId);
        return view('admin.return-balance', compact('settings', 'user'));
    }

    /**
     * Store return balance (deduct).
     */
    public function storeReturnBalance(Request $request, $userId)
    {
        $validated = $request->validate([
            'balance_type' => ['required', 'in:main_bal,drive_bal,bank_bal'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'pin' => ['required', 'digits:4'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()
                ->withErrors(['pin' => 'Invalid admin PIN.'])
                ->withInput();
        }

        $user = User::findOrFail($userId);
        $balanceType = $validated['balance_type'];
        $description = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : null;

        // Check if user has enough balance
        if ($user->$balanceType < $validated['amount']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Insufficient balance!');
        }

        $user->$balanceType -= $validated['amount'];
        $user->save();

        // Record balance deduction in history
        $balanceTypeName = $this->balanceTypeLabel($balanceType);

        $this->storeBalanceHistory($user, $validated['amount'], 'Returned: ' . $balanceTypeName, $description);

        $this->notifyUser(
            $user,
            'Balance Returned',
            $this->balanceNotificationMessage(
                'returned from',
                number_format((float) $validated['amount'], 2),
                $balanceTypeName,
                $description,
            ),
            route('dashboard'),
        );

        return redirect()->route('admin.resellers')->with('success', 'Balance returned successfully!');
    }

    /**
     * Display all resellers.
     */
    public function allResellers()
    {
        $settings = HomepageSetting::first();
        $users = User::where('is_admin', false)->orderBy('created_at', 'desc')->get();
        return view('admin.all-resellers', compact('settings', 'users'));
    }

    /**
     * Display a listing of resellers (all users for now).
     */
    public function resellers(Request $request)
    {
        $level = $request->query('level');
        $username = $request->query('username');
        $status = $request->query('status');

        $query = User::where('is_admin', false)->with('parent');

        if ($level) {
            $query->where('level', $level);
        }

        if ($username) {
            $query->where('email', 'like', '%' . $username . '%');
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->get();
        $settings = HomepageSetting::first();
        $operators = Operator::all();
        $pendingCount = $this->pendingRequestCount();

        return view('admin', compact('users', 'settings', 'operators', 'level', 'username', 'status', 'pendingCount'));
    }

    public function showReseller(User $user)
    {
        if ($user->is_admin) {
            return redirect()->route('admin.resellers')->with('error', 'Invalid reseller account selected.');
        }

        $settings = HomepageSetting::first();
        $operators = Operator::all();
        $pendingCount = $this->pendingRequestCount();

        return view('admin', compact('settings', 'operators', 'pendingCount', 'user'))
            ->with('resellerUser', $user);
    }

    public function bulkResellerAction(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:active,deactive,delete,cancel_otp'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
        ]);

        $users = User::query()
            ->where('is_admin', false)
            ->whereIn('id', $validated['user_ids'])
            ->get();

        if ($users->isEmpty()) {
            return redirect()->back()->with('error', 'No valid reseller account selected.');
        }

        $userIds = $users->modelKeys();

        switch ($validated['action']) {
            case 'active':
                User::query()->whereIn('id', $userIds)->update(['is_active' => true]);
                $message = 'Selected reseller accounts activated successfully.';
                break;

            case 'deactive':
                User::query()->whereIn('id', $userIds)->update(['is_active' => false]);
                $message = 'Selected reseller accounts deactivated successfully.';
                break;

            case 'delete':
                User::query()->whereIn('id', $userIds)->delete();
                $message = 'Selected reseller accounts deleted successfully.';
                break;

            case 'cancel_otp':
                if (Schema::hasColumn('users', 'otp')) {
                    User::query()->whereIn('id', $userIds)->update(['otp' => null]);
                }

                if (Schema::hasTable('otps')) {
                    $emails = $users->pluck('email')->filter()->unique()->values();

                    if ($emails->isNotEmpty()) {
                        \DB::table('otps')->whereIn('email', $emails)->delete();
                    }

                    if (Schema::hasColumn('otps', 'mobile')) {
                        $mobiles = $users->pluck('mobile')->filter()->unique()->values();

                        if ($mobiles->isNotEmpty()) {
                            \DB::table('otps')->whereIn('mobile', $mobiles)->delete();
                        }
                    }
                }

                $message = 'Selected reseller OTPs cancelled successfully.';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    public function deletedAccounts()
    {
        $settings = HomepageSetting::first();
        $pendingCount = $this->pendingRequestCount();
        $deletedUsers = User::onlyTrashed()
            ->where('is_admin', false)
            ->with('parent')
            ->orderByDesc('deleted_at')
            ->get();

        return view('admin', compact('settings', 'deletedUsers', 'pendingCount'));
    }

    public function restoreDeletedAccount(int $userId)
    {
        $user = User::withTrashed()
            ->where('is_admin', false)
            ->findOrFail($userId);

        if (! $user->trashed()) {
            return redirect()->route('admin.deleted.accounts')->with('error', 'Reseller account is not deleted.');
        }

        $user->restore();

        return redirect()->route('admin.deleted.accounts')->with('success', 'Reseller account restored successfully.');
    }

    /**
     * Toggle active/inactive status for a user.
     */
    public function toggleStatus(User $user)
    {
        // protect admins from being toggled accidentally
        if ($user->is_admin) {
            return redirect()->back()->with('error', 'Cannot change status of admin users.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return redirect()->back()->with('success', 'User status updated.');
    }

    /**
     * Store a new user.
     */
    public function storeUser(Request $request)
    {
        $allowedPermissionKeys = array_keys(User::resellerPermissionOptions());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'pin' => ['required', 'digits:4'],
            'level' => ['required', 'in:house,dgm,dealer,seller,retailer'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', $allowedPermissionKeys)],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Hash::make($validated['password']),
            'pin' => \Hash::make($validated['pin']),
            'is_admin' => false,
            'is_active' => true,
            'level' => $validated['level'],
            'parent_id' => auth()->id(),
            'permissions' => json_encode($this->filterPermissionKeys($validated['permissions'] ?? [], $allowedPermissionKeys)),
        ]);

        return redirect()->route('admin.resellers')->with('success', 'User created successfully.');
    }

    public function updateReseller(Request $request, User $user)
    {
        if ($user->is_admin) {
            return redirect()->route('admin.resellers')->with('error', 'Invalid reseller account selected.');
        }

        $allowedPermissionKeys = array_keys(User::resellerPermissionOptions());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'min:6'],
            'pin' => ['nullable', 'digits:4'],
            'admin_pin' => ['required', 'digits:4'],
            'level' => ['required', 'in:house,dgm,dealer,seller,retailer'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', $allowedPermissionKeys)],
        ]);

        if (! $this->hasValidAdminPin($validated['admin_pin'])) {
            return redirect()->back()
                ->withErrors(['admin_pin' => 'Invalid admin PIN.'])
                ->withInput();
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->level = $validated['level'];
        $user->permissions = json_encode($this->filterPermissionKeys($validated['permissions'] ?? [], $allowedPermissionKeys));

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        if (filled($validated['pin'] ?? null)) {
            $user->pin = $validated['pin'];
        }

        $user->save();

        return redirect()->route('admin.resellers.show', $user)
            ->with('success', 'Reseller details updated successfully.');
    }

    /**
     * Show backup page.
     */
    public function backup()
    {
        $settings = HomepageSetting::first();
        return view('admin.backup', compact('settings'));
    }

    /**
     * Download user backup as CSV.
     */
    public function downloadBackup()
    {
        $users = User::where('is_admin', false)->get();

        $filename = 'user_backup_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Level', 'Main Balance', 'Bank Balance', 'Drive Balance', 'Stock', 'Status', 'Created At']);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->level ?? 'N/A',
                    $user->main_bal ?? '0.00',
                    $user->bank_bal ?? '0.00',
                    $user->drive_bal ?? '0.00',
                    $user->stock ?? '0.00',
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show admin profile.
     */
    public function profile(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);
        $admin = auth()->user();
        $googleOtpSetupSecret = null;
        $googleOtpOtpAuthUrl = null;
        $googleOtpMaskedSecret = null;

        if ($settings->google_otp_enabled) {
            /** @var GoogleOtpService $googleOtpService */
            $googleOtpService = app(GoogleOtpService::class);

            if ($admin->google_otp_enabled && filled($admin->google_otp_secret)) {
                $googleOtpSetupSecret = $admin->google_otp_secret;
                $googleOtpMaskedSecret = $googleOtpService->maskSecret($admin->google_otp_secret);
            } else {
                $googleOtpSetupSecret = (string) $request->session()->get('admin_google_otp_setup_secret', $googleOtpService->generateSecret());
                $request->session()->put('admin_google_otp_setup_secret', $googleOtpSetupSecret);
            }

            $issuer = $settings->google_otp_issuer ?: $settings->company_name ?: config('app.name', 'Codecartel Telecom');
            $googleOtpOtpAuthUrl = $googleOtpService->buildOtpAuthUrl($issuer, $admin->email, $googleOtpSetupSecret);
        }

        return view('admin.profile', compact(
            'settings',
            'admin',
            'googleOtpSetupSecret',
            'googleOtpOtpAuthUrl',
            'googleOtpMaskedSecret'
        ));
    }

    public function enableGoogleOtp(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);

        if (! $settings->google_otp_enabled) {
            return redirect()->route('admin.profile')->withErrors([
                'otp' => 'Google OTP is currently disabled from admin settings.',
            ]);
        }

        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        /** @var GoogleOtpService $googleOtpService */
        $googleOtpService = app(GoogleOtpService::class);
        $admin = $request->user();
        $secret = $admin->google_otp_enabled && filled($admin->google_otp_secret)
            ? (string) $admin->google_otp_secret
            : (string) $request->session()->get('admin_google_otp_setup_secret');

        if (blank($secret)) {
            $secret = $googleOtpService->generateSecret();
            $request->session()->put('admin_google_otp_setup_secret', $secret);
        }

        if (! $googleOtpService->verifyCode($secret, $validated['otp'])) {
            return back()->withErrors([
                'otp' => 'Invalid Google Authenticator OTP.',
            ])->withInput();
        }

        $admin->forceFill([
            'google_otp_secret' => $secret,
            'google_otp_enabled' => true,
            'google_otp_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('admin_google_otp_setup_secret');

        return redirect()->route('admin.profile')->with('success', 'Google Authenticator enabled successfully for your admin account!');
    }

    public function disableGoogleOtp(Request $request)
    {
        $validated = $request->validate([
            'disable_pin' => ['required', 'digits:4'],
        ]);

        $admin = $request->user();

        if (! $admin->pin || ! Hash::check($validated['disable_pin'], $admin->pin)) {
            return back()->withErrors([
                'disable_pin' => 'Current admin PIN is incorrect.',
            ]);
        }

        $admin->forceFill([
            'google_otp_secret' => null,
            'google_otp_enabled' => false,
            'google_otp_confirmed_at' => null,
        ])->save();

        $request->session()->forget('admin_google_otp_setup_secret');

        return redirect()->route('admin.profile')->with('success', 'Google Authenticator disabled successfully for your admin account!');
    }

    /**
     * Show edit profile form.
     */
    public function editProfile()
    {
        $settings = HomepageSetting::first();
        $admin = auth()->user();
        return view('admin.profile-edit', compact('settings', 'admin'));
    }

    /**
     * Update admin profile.
     */
    public function updateProfile(Request $request)
    {
        $admin = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $admin->id],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username,' . $admin->id],
            'mobile' => ['nullable', 'string', 'max:20'],
            'nid' => ['nullable', 'string', 'max:50'],
        ]);

        $admin->update($validated);

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully!');
    }

    /**
     * Update profile picture.
     */
    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $admin = auth()->user();

        // Delete old picture if exists
        if ($admin->profile_picture) {
            \Storage::disk('public')->delete($admin->profile_picture);
        }

        // Store new picture
        $path = $request->file('profile_picture')->store('profile-pictures', 'public');
        $admin->update(['profile_picture' => $path]);

        return redirect()->route('admin.profile')->with('picture_success', 'Profile picture updated successfully!');
    }

    /**
     * Delete profile picture.
     */
    public function deleteProfilePicture()
    {
        $admin = auth()->user();

        if ($admin->profile_picture) {
            \Storage::disk('public')->delete($admin->profile_picture);
            $admin->update(['profile_picture' => null]);
        }

        return redirect()->route('admin.profile')->with('picture_success', 'Profile picture removed successfully!');
    }

    /**
     * Show manage admin users page.
     */
    public function manageAdmins()
    {
        $settings = HomepageSetting::first();
        $admins = User::where('is_admin', true)->get();
        $isFirstAdmin = auth()->user()->is_first_admin ?? false;

        return view('admin.manage-admins', compact('settings', 'admins', 'isFirstAdmin'));
    }

    /**
     * Store new admin user (only first admin can create).
     */
    public function storeAdmin(Request $request)
    {
        if (!auth()->user()->is_first_admin) {
            return redirect()->back()->with('error', 'Only the first admin can create new admin accounts.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'pin' => ['required', 'digits:4'],
            'permissions' => ['nullable', 'array'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Hash::make($validated['password']),
            'pin' => \Hash::make($validated['pin']),
            'is_admin' => true,
            'is_first_admin' => false,
            'is_active' => true,
            'permissions' => json_encode($validated['permissions'] ?? []),
        ]);

        return redirect()->route('admin.manage.admins')->with('success', 'Admin user created successfully.');
    }

    /**
     * Show change password/PIN page.
     */
    public function showChangeCredentials()
    {
        $settings = HomepageSetting::first();
        return view('admin.change-credentials', compact('settings'));
    }

    /**
     * Update admin password.
     */
    public function updatePassword(Request $request)
    {
        $securityRuntime = app(SecurityRuntimeService::class);

        $validated = $request->validate([
            'new_password' => $securityRuntime->passwordRules(),
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['new_password']),
            'password_changed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully!');
    }

    /**
     * Update admin PIN.
     */
    public function updatePin(Request $request)
    {
        $validated = $request->validate([
            'new_pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        auth()->user()->update([
            'pin' => Hash::make($validated['new_pin']),
            'pin_changed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'PIN updated successfully!');
    }

    /**
     * Show drive offer page.
     */
    public function driveOffer()
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();

        $operatorsList = [
            ['name' => 'Robi', 'code' => 'RB'],
            ['name' => 'GrameenPhone', 'code' => 'GP'],
            ['name' => 'Teletalk', 'code' => 'TT'],
            ['name' => 'Banglalink', 'code' => 'BL'],
            ['name' => 'Airtel', 'code' => 'AT']
        ];
        $driveData = [];

        foreach ($operatorsList as $operator) {
            $active = DrivePackage::where('operator', $operator['name'])->where('status', 'active')->count();
            $deactive = DrivePackage::where('operator', $operator['name'])->where('status', 'deactive')->count();

            $driveData[] = [
                'operator' => $operator['name'],
                'opcode' => $operator['code'],
                'active' => $active,
                'deactive' => $deactive
            ];
        }

        return view('admin.drive-offer', compact('settings', 'operators', 'driveData'));
    }

    /**
     * Show manage drive package page.
     */
    public function manageDrivePackage($operator)
    {
        $settings = HomepageSetting::first();
        $packages = DrivePackage::where('operator', $operator)
            ->when(request('search'), function ($query) {
                $query->where('name', 'like', '%' . request('search') . '%');
            })
            ->latest('id')
            ->get();
        return view('admin.manage-drive-package', compact('settings', 'operator', 'packages'));
    }

    /**
     * Show edit drive package page.
     */
    public function editDrivePackage($operator, $id)
    {
        $settings = HomepageSetting::first();
        $package = DrivePackage::findOrFail($id);
        return view('admin.edit-drive-package', compact('settings', 'package'));
    }

    /**
    
     */
    public function storeDrivePackage(Request $request, $operator)
    {
        $validated = $request->validate([
            'package_name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0',
            'expire' => 'required|date',
            'status' => 'required|in:active,deactive'
        ]);

        DrivePackage::create([
            'operator' => $operator,
            'name' => $validated['package_name'],
            'price' => $validated['price'],
            'commission' => $validated['commission'],
            'expire' => $validated['expire'],
            'status' => $validated['status'],
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0
        ]);

        return redirect()->route('admin.manage.drive.package', ['operator' => $operator])
            ->with('success', 'Package added successfully!');
    }

    /**
     * Update drive package.
     */
    public function updateDrivePackage(Request $request, $operator, $id)
    {
        $validated = $request->validate([
            'package_name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0',
            'expire' => 'required|date',
            'status' => 'required|in:active,deactive'
        ]);

        $package = DrivePackage::findOrFail($id);
        $package->update([
            'name' => $validated['package_name'],
            'price' => $validated['price'],
            'commission' => $validated['commission'],
            'expire' => $validated['expire'],
            'status' => $validated['status']
        ]);

        return redirect()->route('admin.manage.drive.package', ['operator' => $operator])
            ->with('success', 'Package updated successfully!');
    }

    /**
     * Update drive package list from API.
     */
    public function updateDrivePackageFromApi($operator)
    {
        // Add your API update logic here
        return redirect()->route('admin.manage.drive.package', ['operator' => $operator])
            ->with('success', 'Package list updated from API successfully!');
    }

    /**
     * Show regular offer page.
     */
    public function regularOffer()
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();

        $operatorsList = [
            ['name' => 'Robi', 'code' => 'RB'],
            ['name' => 'GrameenPhone', 'code' => 'GP'],
            ['name' => 'Teletalk', 'code' => 'TT'],
            ['name' => 'Banglalink', 'code' => 'BL'],
            ['name' => 'Airtel', 'code' => 'AT']
        ];
        $regularData = [];

        foreach ($operatorsList as $operator) {
            $active = RegularPackage::where('operator', $operator['name'])->where('status', 'active')->count();
            $deactive = RegularPackage::where('operator', $operator['name'])->where('status', 'deactive')->count();

            $regularData[] = [
                'operator' => $operator['name'],
                'opcode' => $operator['code'],
                'active' => $active,
                'deactive' => $deactive
            ];
        }

        return view('admin.regular-offer', compact('settings', 'operators', 'regularData'));
    }

    /**
     * Show manage regular package page.
     */
    public function manageRegularPackage($operator)
    {
        $settings = HomepageSetting::first();
        $packages = RegularPackage::where('operator', $operator)
            ->when(request('search'), function ($query) {
                $query->where('name', 'like', '%' . request('search') . '%');
            })
            ->latest('id')
            ->get();
        return view('admin.manage-regular-package', compact('settings', 'operator', 'packages'));
    }

    /**
     * Show edit regular package page.
     */
    public function editRegularPackage($operator, $id)
    {
        $settings = HomepageSetting::first();
        $package = RegularPackage::findOrFail($id);
        return view('admin.edit-regular-package', compact('settings', 'package'));
    }

    /**
     * Store new regular package.
     */
    public function storeRegularPackage(Request $request, $operator)
    {
        $validated = $request->validate([
            'package_name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0',
            'expire' => 'required|date',
            'status' => 'required|in:active,deactive'
        ]);

        RegularPackage::create([
            'operator' => $operator,
            'name' => $validated['package_name'],
            'price' => $validated['price'],
            'commission' => $validated['commission'],
            'expire' => $validated['expire'],
            'status' => $validated['status'],
            'sell_today' => 0,
            'amount' => 0,
            'comm' => 0
        ]);

        return redirect()->route('admin.manage.regular.package', ['operator' => $operator])
            ->with('success', 'Package added successfully!');
    }

    /**
     * Update regular package.
     */
    public function updateRegularPackage(Request $request, $operator, $id)
    {
        $validated = $request->validate([
            'package_name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0',
            'expire' => 'required|date',
            'status' => 'required|in:active,deactive'
        ]);

        $package = RegularPackage::findOrFail($id);
        $package->update([
            'name' => $validated['package_name'],
            'price' => $validated['price'],
            'commission' => $validated['commission'],
            'expire' => $validated['expire'],
            'status' => $validated['status']
        ]);

        return redirect()->route('admin.manage.regular.package', ['operator' => $operator])
            ->with('success', 'Package updated successfully!');
    }

    /**
     * Update regular package list from API.
     */
    public function updateRegularPackageFromApi($operator)
    {
        // Add your API update logic here
        return redirect()->route('admin.manage.regular.package', ['operator' => $operator])
            ->with('success', 'Package list updated from API successfully!');
    }

    /**
     * Show pending drive requests.
     */
    public function pendingDriveRequests(Request $request)
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();

        $show = (int) $request->query('show', 50);
        if (! in_array($show, [25, 50, 100], true)) {
            $show = 50;
        }

        $number = $request->query('number');
        $reseller = $request->query('reseller');
        $service = $request->query('service');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $driveRequests = \App\Models\DriveRequest::where('status', 'pending')
            ->with(['user', 'package'])
            ->get()
            ->map(function ($item) {
                $item->request_type = 'Drive';
                $item->display_status = $item->admin_status ?? $item->status;
                return $item;
            });

        $regularRequests = \App\Models\RegularRequest::where('status', 'pending')
            ->with(['user', 'package'])
            ->get()
            ->map(function ($item) {
                $item->request_type = 'Internet';
                $item->display_status = $item->admin_status ?? $item->status;
                return $item;
            });

        $flexiRequests = Schema::hasTable('flexi_requests')
            ? \App\Models\FlexiRequest::where('status', 'pending')
            ->with('user')
            ->get()
            ->map(function ($item) {
                $item->request_type = 'Flexi';
                $item->display_status = $item->status;
                return $item;
            })
            : collect();

        $manualRequests = Schema::hasTable('manual_payment_requests')
            ? ManualPaymentRequest::where('status', 'pending')
            ->with('user')
            ->get()
            ->map(function ($item) {
                $item->request_type = $item->method;
                $item->request_category = 'manual_payment';
                $item->display_status = $item->status;
                $item->operator = $item->method;
                $item->mobile = $item->sender_number;
                $item->type = $item->transaction_id;
                return $item;
            })
            : collect();

        $requests = $driveRequests
            ->concat($regularRequests)
            ->concat($flexiRequests)
            ->concat($manualRequests)
            ->sortByDesc('created_at')
            ->values();

        if ($number) {
            $requests = $requests->filter(function ($item) use ($number) {
                return stripos((string) ($item->mobile ?? ''), $number) !== false
                    || stripos((string) ($item->operator ?? ''), $number) !== false
                    || stripos((string) ($item->transaction_id ?? ''), $number) !== false;
            })->values();
        }

        if ($reseller) {
            $requests = $requests->filter(function ($item) use ($reseller) {
                return stripos((string) ($item->user->name ?? ''), $reseller) !== false;
            })->values();
        }

        if ($service) {
            $requests = $requests->filter(function ($item) use ($service) {
                return strtolower((string) ($item->request_type ?? 'Drive')) === strtolower($service);
            })->values();
        }

        if ($status) {
            $requests = $requests->filter(function ($item) use ($status) {
                return strtolower((string) ($item->display_status ?? $item->status ?? '')) === strtolower($status);
            })->values();
        }

        if ($dateFrom || $dateTo) {
            $startDate = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : null;
            $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : null;

            $requests = $requests->filter(function ($item) use ($startDate, $endDate) {
                $createdAt = $item->created_at instanceof \Carbon\Carbon
                    ? $item->created_at
                    : \Carbon\Carbon::parse($item->created_at);

                if ($startDate && $createdAt->lt($startDate)) {
                    return false;
                }

                if ($endDate && $createdAt->gt($endDate)) {
                    return false;
                }

                return true;
            })->values();
        }

        $pendingCount = $this->pendingRequestCount();
        $totalAmount = $requests->sum('amount');
        $requests = $requests->take($show)->values();
        $totalUsers = 0;
        $today = 0;
        $yesterday = 0;
        $balanceToday = 0;
        $balanceYesterday = 0;
        $operatorSales = collect();
        $bankingSales = collect();
        return view('admin', compact('settings', 'operators', 'requests', 'pendingCount', 'totalAmount', 'totalUsers', 'today', 'yesterday', 'balanceToday', 'balanceYesterday', 'operatorSales', 'bankingSales', 'show', 'number', 'reseller', 'service', 'status', 'dateFrom', 'dateTo'));
    }

    public function bulkPendingRequestAction(Request $request)
    {
        $filterQuery = $this->pendingRequestFilterQuery($request);

        $validated = $request->validate([
            'bulk_action' => ['required', 'in:resend,waiting,manual_complete,process,cancel'],
            'request_keys' => ['nullable', 'array'],
            'request_keys.*' => ['required', 'regex:/^(drive|internet):[0-9]+$/'],
            'bulk_note' => ['nullable', 'string', 'max:1000'],
            'pin' => ['required', 'digits:4'],
        ]);

        $requestKeys = collect($validated['request_keys'] ?? [])->unique()->values();

        if ($requestKeys->isEmpty()) {
            return redirect()
                ->route('admin.pending.drive.requests', $filterQuery)
                ->withInput($request->except('pin'))
                ->with('error', 'Please select at least one Drive or Internet pending request.');
        }

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()
                ->route('admin.pending.drive.requests', $filterQuery)
                ->withInput($request->except('pin'))
                ->with('error', 'Invalid PIN!');
        }

        $action = $validated['bulk_action'];
        $note = trim((string) ($validated['bulk_note'] ?? ''));
        $selectedRequests = $requestKeys
            ->map(function (string $requestKey) {
                [$type, $id] = explode(':', $requestKey, 2);

                return [
                    'type' => $type,
                    'id' => (int) $id,
                ];
            });

        if (
            in_array($action, ['resend', 'waiting', 'process'], true)
            && (! Schema::hasColumn('drive_requests', 'admin_status')
                || ! Schema::hasColumn('regular_requests', 'admin_status'))
        ) {
            return redirect()
                ->route('admin.pending.drive.requests', $filterQuery)
                ->withInput($request->except('pin'))
                ->with('error', 'Please run the latest pending request migration before using this bulk action.');
        }

        $processed = 0;

        foreach ($selectedRequests as $selectedRequest) {
            $processed += match ($action) {
                'manual_complete' => $selectedRequest['type'] === 'drive'
                    ? (int) $this->bulkCompleteDriveRequest($selectedRequest['id'], $note)
                    : (int) $this->bulkCompleteRegularRequest($selectedRequest['id'], $note),
                'cancel' => $selectedRequest['type'] === 'drive'
                    ? (int) $this->bulkCancelDriveRequest($selectedRequest['id'])
                    : (int) $this->bulkCancelRegularRequest($selectedRequest['id']),
                default => $selectedRequest['type'] === 'drive'
                    ? (int) $this->bulkUpdateDriveRequestWorkflow($selectedRequest['id'], $action, $note)
                    : (int) $this->bulkUpdateRegularRequestWorkflow($selectedRequest['id'], $action, $note),
            };
        }

        if ($processed === 0) {
            return redirect()
                ->route('admin.pending.drive.requests', $filterQuery)
                ->withInput($request->except('pin'))
                ->with('error', 'No pending requests were updated.');
        }

        return redirect()
            ->route('admin.pending.drive.requests', $filterQuery)
            ->with('success', 'Selected pending requests updated successfully.');
    }

    protected function pendingRequestFilterQuery(Request $request): array
    {
        return collect($request->only([
            'show',
            'number',
            'reseller',
            'service',
            'status',
            'date_from',
            'date_to',
        ]))
            ->filter(fn($value) => $value !== null && $value !== '')
            ->all();
    }

    protected function clearPendingWorkflowColumns(string $table): array
    {
        $payload = [];

        if (Schema::hasColumn($table, 'admin_status')) {
            $payload['admin_status'] = null;
        }

        if (Schema::hasColumn($table, 'admin_note')) {
            $payload['admin_note'] = null;
        }

        return $payload;
    }

    protected function bulkUpdateDriveRequestWorkflow(int $id, string $action, string $note = ''): bool
    {
        $driveRequest = \App\Models\DriveRequest::with('user')
            ->where('status', 'pending')
            ->find($id);

        if (! $driveRequest || ! Schema::hasColumn('drive_requests', 'admin_status')) {
            return false;
        }

        $payload = ['admin_status' => $action];
        if (Schema::hasColumn('drive_requests', 'admin_note')) {
            $payload['admin_note'] = $note !== '' ? $note : null;
        }

        $driveRequest->update($payload);

        $message = match ($action) {
            'waiting' => 'is waiting for processing.',
            'process' => 'is being processed.',
            default => 'was resent for processing.',
        };

        $this->notifyUser(
            $driveRequest->user,
            'Drive Request Updated',
            'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' ' . $message,
            route('dashboard'),
        );

        return true;
    }

    protected function bulkUpdateRegularRequestWorkflow(int $id, string $action, string $note = ''): bool
    {
        $regularRequest = \App\Models\RegularRequest::with('user')
            ->where('status', 'pending')
            ->find($id);

        if (! $regularRequest || ! Schema::hasColumn('regular_requests', 'admin_status')) {
            return false;
        }

        $payload = ['admin_status' => $action];

        if (Schema::hasColumn('regular_requests', 'admin_note')) {
            $payload['admin_note'] = $note !== '' ? $note : null;
        }

        if ($note !== '' && Schema::hasColumn('regular_requests', 'description')) {
            $payload['description'] = $note;
        }

        $regularRequest->update($payload);

        $message = match ($action) {
            'waiting' => 'is waiting for processing.',
            'process' => 'is being processed.',
            default => 'was resent for processing.',
        };

        $this->notifyUser(
            $regularRequest->user,
            'Internet Request Updated',
            'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' ' . $message,
            route('dashboard'),
        );

        return true;
    }

    protected function bulkCompleteDriveRequest(int $id, string $note = ''): bool
    {
        $driveRequest = \App\Models\DriveRequest::with('user')
            ->where('status', 'pending')
            ->find($id);

        if (! $driveRequest) {
            return false;
        }

        $sync = $this->syncRoutedSettlement(
            $driveRequest,
            'drive',
            'approved',
            $note !== '' ? $note : 'Bulk manual complete',
        );

        if (! ($sync['ok'] ?? false)) {
            return false;
        }

        $payload = array_merge(['status' => 'approved'], $this->clearPendingWorkflowColumns('drive_requests'));
        $driveRequest->update($payload);

        \DB::table('drive_history')->insert([
            'user_id' => $driveRequest->user_id,
            'package_id' => $driveRequest->package_id,
            'operator' => $driveRequest->operator,
            'mobile' => $driveRequest->mobile,
            'amount' => $driveRequest->amount,
            'status' => 'success',
            'description' => $note !== '' ? $note : 'Bulk manual complete',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->notifyUser(
            $driveRequest->user,
            'Drive Offer Success',
            'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' was approved.',
            route('dashboard'),
        );

        return true;
    }

    protected function bulkCompleteRegularRequest(int $id, string $note = ''): bool
    {
        $regularRequest = \App\Models\RegularRequest::with('user')
            ->where('status', 'pending')
            ->find($id);

        if (! $regularRequest) {
            return false;
        }

        $description = $note !== '' ? $note : 'Bulk manual complete';
        $sync = $this->syncRoutedSettlement($regularRequest, 'internet', 'approved', $description);

        if (! ($sync['ok'] ?? false)) {
            return false;
        }

        $payload = array_merge([
            'status' => 'approved',
            'description' => $description,
        ], $this->clearPendingWorkflowColumns('regular_requests'));

        $regularRequest->update($payload);

        $this->notifyUser(
            $regularRequest->user,
            'Internet Pack Success',
            'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' was approved.',
            route('dashboard'),
        );

        return true;
    }

    protected function bulkCancelDriveRequest(int $id): bool
    {
        $driveRequest = \App\Models\DriveRequest::with('user')
            ->where('status', 'pending')
            ->find($id);

        if (! $driveRequest) {
            return false;
        }

        $isRouted = (bool) ($driveRequest->is_routed ?? false);
        $sync = $this->syncRoutedSettlement($driveRequest, 'drive', 'cancelled');

        if (! ($sync['ok'] ?? false)) {
            return false;
        }

        $payload = array_merge(['status' => 'rejected'], $this->clearPendingWorkflowColumns('drive_requests'));
        $driveRequest->update($payload);

        $user = $driveRequest->user;

        if (! $isRouted) {
            $balanceType = $driveRequest->balance_type ?? 'drive_bal';

            if (! in_array($balanceType, ['drive_bal', 'main_bal'], true)) {
                $balanceType = 'drive_bal';
            }

            $user->{$balanceType} += $driveRequest->amount;
            $user->save();
        }

        $this->notifyUser(
            $user,
            'Drive Offer Cancelled',
            $isRouted
                ? 'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' was cancelled.'
                : 'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' was cancelled and balance was refunded.',
            route('dashboard'),
        );

        return true;
    }

    protected function bulkCancelRegularRequest(int $id): bool
    {
        $regularRequest = \App\Models\RegularRequest::with('user')
            ->where('status', 'pending')
            ->find($id);

        if (! $regularRequest) {
            return false;
        }

        $isRouted = (bool) ($regularRequest->is_routed ?? false);
        $sync = $this->syncRoutedSettlement($regularRequest, 'internet', 'cancelled');

        if (! ($sync['ok'] ?? false)) {
            return false;
        }

        $payload = array_merge(['status' => 'rejected'], $this->clearPendingWorkflowColumns('regular_requests'));
        $regularRequest->update($payload);

        $user = $regularRequest->user;

        if (! $isRouted) {
            $user->main_bal += $regularRequest->amount;
            $user->save();
        }

        $this->notifyUser(
            $user,
            'Internet Pack Cancelled',
            $isRouted
                ? 'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' was cancelled.'
                : 'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' was cancelled and balance was refunded.',
            route('dashboard'),
        );

        return true;
    }

    /**
     * Approve drive request.
     */
    public function approveDriveRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\DriveRequest::with(['user', 'package'])->findOrFail($id);
        return view('admin.confirm-drive-request', compact('settings', 'request'));
    }

    /**
     * Confirm drive request approval.
     */
    public function confirmDriveRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'pin' => 'required|digits:4'
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $driveRequest = \App\Models\DriveRequest::findOrFail($id);
        $description = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : null;
        $sync = $this->syncRoutedSettlement($driveRequest, 'drive', 'approved', $description);

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        // Update request status
        $driveRequest->update(['status' => 'approved']);

        // Add to drive history
        \DB::table('drive_history')->insert([
            'user_id' => $driveRequest->user_id,
            'package_id' => $driveRequest->package_id,
            'operator' => $driveRequest->operator,
            'mobile' => $driveRequest->mobile,
            'amount' => $driveRequest->amount,
            'status' => 'success',
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $driveRequest->loadMissing('user');

        $this->notifyUser(
            $driveRequest->user,
            'Drive Offer Success',
            'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' was approved.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', 'Request approved successfully!');
    }

    /**
     * Reject drive request.
     */
    public function rejectDriveRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\DriveRequest::with(['user', 'package'])->findOrFail($id);
        return view('admin.confirm-failed-request', compact('settings', 'request'));
    }

    /**
     * Confirm failed drive request.
     */
    public function confirmFailedRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'pin' => 'required|digits:4'
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $driveRequest = \App\Models\DriveRequest::findOrFail($id);
        $description = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : 'Request failed by admin';
        $isRouted = (bool) ($driveRequest->is_routed ?? false);
        $sync = $this->syncRoutedSettlement($driveRequest, 'drive', 'rejected', $description);

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        $driveRequest->update(['status' => 'rejected']);

        // Refund user's original balance source
        $user = $driveRequest->user;

        if (! $isRouted) {
            $balanceType = $driveRequest->balance_type ?? 'drive_bal';

            if (! in_array($balanceType, ['drive_bal', 'main_bal'], true)) {
                $balanceType = 'drive_bal';
            }

            $user->{$balanceType} += $driveRequest->amount;
            $user->save();
        }

        // Add to drive history
        \DB::table('drive_history')->insert([
            'user_id' => $driveRequest->user_id,
            'package_id' => $driveRequest->package_id,
            'operator' => $driveRequest->operator,
            'mobile' => $driveRequest->mobile,
            'amount' => $driveRequest->amount,
            'status' => 'failed',
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->notifyUser(
            $user,
            'Drive Offer Failed',
            $isRouted
                ? 'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' failed.'
                : 'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' failed and balance was refunded.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', $isRouted ? 'Request failed successfully!' : 'Request failed and balance refunded!');
    }

    /**
     * Cancel drive request.
     */
    public function cancelDriveRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\DriveRequest::with(['user', 'package'])->findOrFail($id);
        return view('admin.confirm-cancel-request', compact('settings', 'request'));
    }

    /**
     * Confirm cancel drive request.
     */
    public function confirmCancelRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'pin' => 'required|digits:4'
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $driveRequest = \App\Models\DriveRequest::findOrFail($id);
        $description = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : null;
        $isRouted = (bool) ($driveRequest->is_routed ?? false);
        $sync = $this->syncRoutedSettlement($driveRequest, 'drive', 'cancelled', $description);

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        $driveRequest->update(['status' => 'rejected']);

        // Refund user's original balance source
        $user = $driveRequest->user;

        if (! $isRouted) {
            $balanceType = $driveRequest->balance_type ?? 'drive_bal';

            if (! in_array($balanceType, ['drive_bal', 'main_bal'], true)) {
                $balanceType = 'drive_bal';
            }

            $user->{$balanceType} += $driveRequest->amount;
            $user->save();
        }

        $this->notifyUser(
            $user,
            'Drive Offer Cancelled',
            $isRouted
                ? 'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' was cancelled.'
                : 'Your ' . $driveRequest->operator . ' drive request for ' . $driveRequest->mobile . ' was cancelled and balance was refunded.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', $isRouted ? 'Request cancelled successfully!' : 'Request cancelled and balance refunded!');
    }

    /**
     * Approve regular request (show confirm page).
     */
    public function approveRegularRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\RegularRequest::with(['user', 'package'])->findOrFail($id);
        return view('admin.confirm-regular-request', compact('settings', 'request'));
    }

    /**
     * Confirm regular request approval.
     */
    public function confirmRegularRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'pin' => 'required|digits:4'
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $regularRequest = \App\Models\RegularRequest::findOrFail($id);
        $description = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : 'Success';
        $sync = $this->syncRoutedSettlement($regularRequest, 'internet', 'approved', $description);

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        // Update status and save the admin-entered description
        $regularRequest->update([
            'status' => 'approved',
            'description' => $description
        ]);

        $regularRequest->loadMissing('user');

        $this->notifyUser(
            $regularRequest->user,
            'Internet Pack Success',
            'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' was approved.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', 'Regular request approved successfully!');
    }

    /**
     * Reject regular request (show failed confirm page).
     */
    public function rejectRegularRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\RegularRequest::with(['user', 'package'])->findOrFail($id);
        return view('admin.confirm-failed-regular-request', compact('settings', 'request'));
    }

    /**
     * Confirm failed regular request.
     */
    public function confirmFailedRegularRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'pin' => 'required|digits:4'
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $regularRequest = \App\Models\RegularRequest::with('user')->findOrFail($id);
        $description = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : null;
        $isRouted = (bool) ($regularRequest->is_routed ?? false);
        $sync = $this->syncRoutedSettlement($regularRequest, 'internet', 'rejected', $description);

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        $regularRequest->update(['status' => 'rejected']);

        $user = $regularRequest->user;

        if (! $isRouted) {
            $user->main_bal += $regularRequest->amount;
            $user->save();
        }

        $this->notifyUser(
            $user,
            'Internet Pack Failed',
            $isRouted
                ? 'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' failed.'
                : 'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' failed and balance was refunded.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', $isRouted ? 'Regular request failed successfully!' : 'Regular request failed and balance refunded!');
    }

    /**
     * Cancel regular request (show cancel confirm page).
     */
    public function cancelRegularRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\RegularRequest::with(['user', 'package'])->findOrFail($id);
        return view('admin.confirm-cancel-regular-request', compact('settings', 'request'));
    }

    /**
     * Confirm cancel regular request.
     */
    public function confirmCancelRegularRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'pin' => 'required|digits:4'
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $regularRequest = \App\Models\RegularRequest::with('user')->findOrFail($id);
        $description = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : null;
        $isRouted = (bool) ($regularRequest->is_routed ?? false);
        $sync = $this->syncRoutedSettlement($regularRequest, 'internet', 'cancelled', $description);

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        $regularRequest->update(['status' => 'rejected']);

        $user = $regularRequest->user;

        if (! $isRouted) {
            $user->main_bal += $regularRequest->amount;
            $user->save();
        }

        $this->notifyUser(
            $user,
            'Internet Pack Cancelled',
            $isRouted
                ? 'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' was cancelled.'
                : 'Your ' . $regularRequest->operator . ' internet request for ' . $regularRequest->mobile . ' was cancelled and balance was refunded.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', $isRouted ? 'Regular request cancelled successfully!' : 'Regular request cancelled and balance refunded!');
    }

    /**
     * Approve flexi request (show confirm page).
     */
    public function approveFlexiRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\FlexiRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        return view('admin.confirm-flexi-request', compact('settings', 'request'));
    }

    /**
     * Confirm flexi request approval.
     */
    public function confirmFlexiRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'trnx_id' => 'nullable|string|max:255|unique:flexi_requests,trnx_id,' . $id,
            'pin' => 'required|digits:4',
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $flexiRequest = \App\Models\FlexiRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        $trnxId = trim((string) ($validated['trnx_id'] ?? ''));
        $sync = $this->syncRoutedSettlement($flexiRequest, 'recharge', 'approved', null, $trnxId !== '' ? $trnxId : $flexiRequest->trnx_id);

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        $flexiRequest->update([
            'status' => 'approved',
            'trnx_id' => $trnxId !== '' ? $trnxId : $flexiRequest->trnx_id,
        ]);

        $this->notifyUser(
            $flexiRequest->user,
            'Flexi Request Success',
            'Your ' . $flexiRequest->operator . ' ' . $flexiRequest->type . ' flexi request for ' . $flexiRequest->mobile . ' was approved.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', 'Flexi request approved successfully!');
    }

    /**
     * Reject flexi request (show failed confirm page).
     */
    public function rejectFlexiRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\FlexiRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        return view('admin.confirm-failed-flexi-request', compact('settings', 'request'));
    }

    /**
     * Confirm failed flexi request.
     */
    public function confirmFailedFlexiRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'pin' => 'required|digits:4',
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $flexiRequest = \App\Models\FlexiRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        $user = $flexiRequest->user;
        $isRouted = (bool) ($flexiRequest->is_routed ?? false);
        $sync = $this->syncRoutedSettlement($flexiRequest, 'recharge', 'rejected');

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        \DB::transaction(function () use ($flexiRequest, $user, $isRouted) {
            $flexiRequest->update(['status' => 'rejected']);

            if (! $isRouted) {
                $user->main_bal += $flexiRequest->amount;
                $user->save();
            }
        });

        $this->notifyUser(
            $user,
            'Flexi Request Failed',
            $isRouted
                ? 'Your ' . $flexiRequest->operator . ' ' . $flexiRequest->type . ' flexi request for ' . $flexiRequest->mobile . ' failed.'
                : 'Your ' . $flexiRequest->operator . ' ' . $flexiRequest->type . ' flexi request for ' . $flexiRequest->mobile . ' failed and balance was refunded.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', $isRouted ? 'Flexi request failed successfully!' : 'Flexi request failed and balance refunded!');
    }

    /**
     * Cancel flexi request (show cancel confirm page).
     */
    public function cancelFlexiRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = \App\Models\FlexiRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        return view('admin.confirm-cancel-flexi-request', compact('settings', 'request'));
    }

    /**
     * Confirm cancel flexi request.
     */
    public function confirmCancelFlexiRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'pin' => 'required|digits:4',
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $flexiRequest = \App\Models\FlexiRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        $user = $flexiRequest->user;
        $isRouted = (bool) ($flexiRequest->is_routed ?? false);
        $sync = $this->syncRoutedSettlement($flexiRequest, 'recharge', 'cancelled');

        if (! ($sync['ok'] ?? false)) {
            return redirect()->back()->with('error', $sync['message']);
        }

        \DB::transaction(function () use ($flexiRequest, $user, $isRouted) {
            $flexiRequest->update(['status' => 'rejected']);

            if (! $isRouted) {
                $user->main_bal += $flexiRequest->amount;
                $user->save();
            }
        });

        $this->notifyUser(
            $user,
            'Flexi Request Cancelled',
            $isRouted
                ? 'Your ' . $flexiRequest->operator . ' ' . $flexiRequest->type . ' flexi request for ' . $flexiRequest->mobile . ' was cancelled.'
                : 'Your ' . $flexiRequest->operator . ' ' . $flexiRequest->type . ' flexi request for ' . $flexiRequest->mobile . ' was cancelled and balance was refunded.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', $isRouted ? 'Flexi request cancelled successfully!' : 'Flexi request cancelled and balance refunded!');
    }

    public function approveManualPaymentRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = ManualPaymentRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        return view('admin.confirm-manual-payment-request', compact('settings', 'request'));
    }

    public function confirmManualPaymentRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
            'pin' => 'required|digits:4',
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $manualPaymentRequest = ManualPaymentRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        $user = $manualPaymentRequest->user;
        $adminNote = filled($validated['admin_note'] ?? null)
            ? trim((string) $validated['admin_note'])
            : null;
        $historyDescription = $adminNote ?: ($manualPaymentRequest->method . ' manual payment approved. TxID: ' . $manualPaymentRequest->transaction_id);

        \DB::transaction(function () use ($manualPaymentRequest, $user, $adminNote, $historyDescription) {
            $manualPaymentRequest->update([
                'status' => 'approved',
                'admin_note' => $adminNote,
            ]);

            $user->main_bal += $manualPaymentRequest->amount;
            $user->save();

            if (Schema::hasTable('balance_add_history')) {
                $this->storeBalanceHistory($user, $manualPaymentRequest->amount, strtolower($manualPaymentRequest->method), $historyDescription);
            }
        });

        $this->notifyUser(
            $user,
            'Balance Request Approved',
            'Your ' . $manualPaymentRequest->method . ' balance request of ' . number_format((float) $manualPaymentRequest->amount, 2) . ' Tk was approved and added to your main balance.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', 'Manual payment request approved successfully!');
    }

    public function failedManualPaymentRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = ManualPaymentRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        return view('admin.confirm-failed-manual-payment-request', compact('settings', 'request'));
    }

    public function confirmFailedManualPaymentRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
            'pin' => 'required|digits:4',
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $manualPaymentRequest = ManualPaymentRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        $adminNote = filled($validated['admin_note'] ?? null)
            ? trim((string) $validated['admin_note'])
            : null;

        $manualPaymentRequest->update([
            'status' => 'rejected',
            'admin_note' => $adminNote,
        ]);

        $this->notifyUser(
            $manualPaymentRequest->user,
            'Balance Request Failed',
            'Your ' . $manualPaymentRequest->method . ' balance request of ' . number_format((float) $manualPaymentRequest->amount, 2) . ' Tk was marked as failed.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', 'Manual payment request marked as failed successfully!');
    }

    public function cancelManualPaymentRequest($id)
    {
        $settings = HomepageSetting::first();
        $request = ManualPaymentRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        return view('admin.confirm-cancel-manual-payment-request', compact('settings', 'request'));
    }

    public function confirmCancelManualPaymentRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
            'pin' => 'required|digits:4',
        ]);

        if (! $this->hasValidAdminPin($validated['pin'])) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $manualPaymentRequest = ManualPaymentRequest::with('user')
            ->where('status', 'pending')
            ->findOrFail($id);

        $adminNote = filled($validated['admin_note'] ?? null)
            ? trim((string) $validated['admin_note'])
            : null;

        $manualPaymentRequest->update([
            'status' => 'rejected',
            'admin_note' => $adminNote,
        ]);

        $this->notifyUser(
            $manualPaymentRequest->user,
            'Balance Request Cancelled',
            'Your ' . $manualPaymentRequest->method . ' balance request of ' . number_format((float) $manualPaymentRequest->amount, 2) . ' Tk was cancelled.',
            route('dashboard'),
        );

        return redirect()->route('admin.pending.drive.requests')->with('success', 'Manual payment request cancelled successfully!');
    }

    /**
     * Show drive history.
     */
    public function driveHistory()
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();
        $history = \DB::table('drive_history')
            ->join('users', 'drive_history.user_id', '=', 'users.id')
            ->select('drive_history.*', 'users.name as user_name')
            ->orderBy('drive_history.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->user = (object)['name' => $item->user_name];
                return $item;
            });
        $pendingCount = \App\Models\DriveRequest::where('status', 'pending')->count()
            + \App\Models\RegularRequest::where('status', 'pending')->count();
        $totalAmount = 0;
        $totalUsers = 0;
        $today = 0;
        $yesterday = 0;
        $balanceToday = 0;
        $balanceYesterday = 0;
        $operatorSales = collect();
        $bankingSales = collect();
        return view('admin', compact('settings', 'operators', 'history', 'pendingCount', 'totalAmount', 'totalUsers', 'today', 'yesterday', 'balanceToday', 'balanceYesterday', 'operatorSales', 'bankingSales'));
    }

    protected function defaultServiceModules(): array
    {
        return [
            [
                'title' => 'Flexiload',
                'minimum_amount' => 10,
                'maximum_amount' => 1499,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 1000.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'title' => 'InternetPack',
                'minimum_amount' => 5,
                'maximum_amount' => 5000,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 296.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'title' => 'SMS',
                'minimum_amount' => 1,
                'maximum_amount' => 2000,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 500.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 4,
                'status' => 'active',
            ],
            [
                'title' => 'Internet Banking',
                'minimum_amount' => 500,
                'maximum_amount' => 1000000,
                'minimum_length' => 11,
                'maximum_length' => 20,
                'auto_send_limit' => 20000.00,
                'require_pin' => false,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => true,
                'sort_order' => 5,
                'status' => 'active',
            ],
            [
                'title' => 'Billpay',
                'minimum_amount' => 50,
                'maximum_amount' => 1000,
                'minimum_length' => 5,
                'maximum_length' => 15,
                'auto_send_limit' => 300.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 5,
                'status' => 'active',
            ],
            [
                'title' => 'Sonali Bank Limited',
                'minimum_amount' => 10000,
                'maximum_amount' => 1000000,
                'minimum_length' => 3,
                'maximum_length' => 20,
                'auto_send_limit' => 25000.00,
                'require_pin' => true,
                'require_name' => true,
                'require_nid' => true,
                'require_sender' => true,
                'sort_order' => 5,
                'status' => 'active',
            ],
            [
                'title' => 'Bulk Flexi',
                'minimum_amount' => 10,
                'maximum_amount' => 5000,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 500.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 8,
                'status' => 'active',
            ],
            [
                'title' => 'GlobalFlexi',
                'minimum_amount' => 10,
                'maximum_amount' => 5000,
                'minimum_length' => 5,
                'maximum_length' => 13,
                'auto_send_limit' => 500.00,
                'require_pin' => false,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 8,
                'status' => 'active',
            ],
            [
                'title' => 'PrepaidCard',
                'minimum_amount' => 9,
                'maximum_amount' => 5000,
                'minimum_length' => 5,
                'maximum_length' => 30,
                'auto_send_limit' => 1000.00,
                'require_pin' => false,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 10,
                'status' => 'active',
            ],
            [
                'title' => 'BillPay2',
                'minimum_amount' => 10,
                'maximum_amount' => 100000,
                'minimum_length' => 3,
                'maximum_length' => 90,
                'auto_send_limit' => 5000.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => false,
                'sort_order' => 10,
                'status' => 'active',
            ],
            [
                'title' => 'BPO',
                'minimum_amount' => 1000,
                'maximum_amount' => 50000,
                'minimum_length' => 11,
                'maximum_length' => 11,
                'auto_send_limit' => 10200.00,
                'require_pin' => true,
                'require_name' => false,
                'require_nid' => false,
                'require_sender' => true,
                'sort_order' => 12,
                'status' => 'active',
            ],
        ];
    }

    protected function serviceModuleValidationRules(?ServiceModule $serviceModule = null): array
    {
        $titleRule = Rule::unique('service_modules', 'title');

        if ($serviceModule !== null) {
            $titleRule = $titleRule->ignore($serviceModule->id);
        }

        return [
            'title' => ['required', 'string', 'max:255', $titleRule],
            'minimum_amount' => ['required', 'numeric', 'min:0'],
            'maximum_amount' => ['required', 'numeric', 'gte:minimum_amount'],
            'minimum_length' => ['required', 'integer', 'min:1'],
            'maximum_length' => ['required', 'integer', 'gte:minimum_length'],
            'auto_send_limit' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,deactive'],
            'require_pin' => ['nullable', 'boolean'],
            'require_name' => ['nullable', 'boolean'],
            'require_nid' => ['nullable', 'boolean'],
            'require_sender' => ['nullable', 'boolean'],
        ];
    }

    protected function validatedServiceModulePayload(Request $request, ?ServiceModule $serviceModule = null): array
    {
        $validated = $request->validate($this->serviceModuleValidationRules($serviceModule));
        $validated['require_pin'] = $request->boolean('require_pin');
        $validated['require_name'] = $request->boolean('require_name');
        $validated['require_nid'] = $request->boolean('require_nid');
        $validated['require_sender'] = $request->boolean('require_sender');

        return $validated;
    }

    protected function findServiceModuleOrFail($serviceModule): ServiceModule
    {
        return ServiceModule::query()->findOrFail($serviceModule);
    }

    protected function rechargeBlockServiceOptions(): array
    {
        return [
            'Flexiload' => 'Flexiload',
            'InternetPack' => 'InternetPack',
        ];
    }

    protected function normalizeRechargeBlockOperator(?string $operator): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $operator));

        return match ($normalized) {
            'GRAMEENPHONE', 'GP' => 'GP',
            'ROBI', 'RB' => 'RB',
            'AIRTEL', 'AT' => 'AT',
            'BANGLALINK', 'BL' => 'BL',
            'TELETALK', 'TT' => 'TT',
            default => substr($normalized, 0, 20),
        };
    }

    protected function rechargeBlockOperatorOptions($operators): array
    {
        $fallbackOptions = [
            'GP' => 'Grameenphone',
            'RB' => 'Robi',
            'AT' => 'Airtel',
            'BL' => 'Banglalink',
            'TT' => 'Teletalk',
        ];

        $databaseOptions = collect($operators)->mapWithKeys(function ($operator) {
            $label = trim((string) ($operator->name ?? $operator['name'] ?? ''));
            $optionValue = $this->normalizeRechargeBlockOperator((string) ($operator->short_code ?? $operator['short_code'] ?? $label));

            return $label !== '' && $optionValue !== ''
                ? [$optionValue => $label]
                : [];
        })->all();

        return array_replace($fallbackOptions, $databaseOptions);
    }

    protected function validatedRechargeBlockListPayload(Request $request): array
    {
        $validated = $request->validate([
            'service' => ['required', Rule::in(array_keys($this->rechargeBlockServiceOptions()))],
            'operator' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return [
            'service' => $validated['service'],
            'operator' => $this->normalizeRechargeBlockOperator($validated['operator']),
            'amount' => round((float) $validated['amount'], 2),
        ];
    }

    protected function findRechargeBlockListOrFail($rechargeBlockList): RechargeBlockList
    {
        return RechargeBlockList::query()->findOrFail($rechargeBlockList);
    }

    protected function securitySettingsDefaults(): array
    {
        return [
            'security_ssl_https_redirect' => 'disable',
            'security_admin_login_captcha' => 'disable',
            'security_reseller_login_captcha' => 'disable',
            'security_pin_expire_days' => 100,
            'security_password_expire_days' => 100,
            'security_password_strong' => 'yes',
            'security_minimum_pin_length' => 4,
            'security_request_interval_minutes' => 1,
            'security_session_timeout_minutes' => 20000,
            'security_support_ticket' => 'enable',
            'security_send_otp_via' => 'sms_modem',
            'security_send_alert_via' => 'sms_modem',
            'security_send_offline_sms_via' => 'sms_modem',
            'security_bulk_flexi_limit' => 1000,
            'security_auto_sending_limit' => 999,
            'security_reseller_overpayment_limit' => 'no',
            'security_modem' => 'modem_v1',
            'security_daily_limit' => 5000000,
            'security_gp' => 'off',
            'security_robi' => 'off',
            'security_banglalink' => 'off',
            'security_airtel' => 'off',
            'security_teletalk' => 'off',
            'security_skitto' => 'off',
            'security_popup_notice' => 'on',
            'security_sms_sent_system' => 'only_offline',
            'security_bank_balance' => 'on',
            'security_drive_balance' => 'off',
            'security_balance_transfer' => 'on',
            'security_commission_system' => 'all_level',
        ];
    }

    protected function securitySettingsColumnNames(): array
    {
        return array_keys($this->securitySettingsDefaults());
    }

    protected function securitySettingsSchemaReady(): bool
    {
        if (! Schema::hasTable('homepage_settings')) {
            return false;
        }

        foreach ($this->securitySettingsColumnNames() as $column) {
            if (! Schema::hasColumn('homepage_settings', $column)) {
                return false;
            }
        }

        return true;
    }

    protected function securitySettingOptions(): array
    {
        return [
            'enable_disable' => [
                'disable' => 'Disable',
                'enable' => 'Enable',
            ],
            'yes_no' => [
                'yes' => 'Yes',
                'no' => 'No',
            ],
            'on_off' => [
                'on' => 'On',
                'off' => 'Off',
            ],
            'delivery_channels' => [
                'sms_modem' => 'SMS Modem',
                'sms_api' => 'SMS API',
                'email' => 'Email',
            ],
            'modems' => [
                'modem_v1' => 'Modem V.1',
                'modem_v2' => 'Modem V.2',
                'api_gateway' => 'API Gateway',
            ],
            'sms_sent_systems' => [
                'only_offline' => 'Only Offline',
                'only_online' => 'Only Online',
                'online_offline' => 'Online + Offline',
            ],
            'commission_systems' => [
                'all_level' => 'All level',
                'single_level' => 'Single level',
                'off' => 'Off',
            ],
        ];
    }

    protected function resolvedSecuritySettings(HomepageSetting $settings): array
    {
        return collect($this->securitySettingsDefaults())->mapWithKeys(function ($default, $key) use ($settings) {
            $value = $settings->{$key} ?? null;

            return [$key => $value !== null ? $value : $default];
        })->all();
    }

    protected function securitySettingsValidationRules(): array
    {
        $options = $this->securitySettingOptions();

        return [
            'security_ssl_https_redirect' => ['required', Rule::in(array_keys($options['enable_disable']))],
            'security_admin_login_captcha' => ['required', Rule::in(array_keys($options['enable_disable']))],
            'security_reseller_login_captcha' => ['required', Rule::in(array_keys($options['enable_disable']))],
            'security_pin_expire_days' => ['required', 'integer', 'min:0'],
            'security_password_expire_days' => ['required', 'integer', 'min:0'],
            'security_password_strong' => ['required', Rule::in(array_keys($options['yes_no']))],
            'security_minimum_pin_length' => ['required', 'integer', 'min:1'],
            'security_request_interval_minutes' => ['required', 'integer', 'min:0'],
            'security_session_timeout_minutes' => ['required', 'integer', 'min:1'],
            'security_support_ticket' => ['required', Rule::in(array_keys($options['enable_disable']))],
            'security_send_otp_via' => ['required', Rule::in(array_keys($options['delivery_channels']))],
            'security_send_alert_via' => ['required', Rule::in(array_keys($options['delivery_channels']))],
            'security_send_offline_sms_via' => ['required', Rule::in(array_keys($options['delivery_channels']))],
            'security_bulk_flexi_limit' => ['required', 'integer', 'min:0'],
            'security_auto_sending_limit' => ['required', 'integer', 'min:0'],
            'security_reseller_overpayment_limit' => ['required', Rule::in(array_keys($options['yes_no']))],
            'security_modem' => ['required', Rule::in(array_keys($options['modems']))],
            'security_daily_limit' => ['required', 'integer', 'min:0'],
            'security_gp' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_robi' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_banglalink' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_airtel' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_teletalk' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_skitto' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_popup_notice' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_sms_sent_system' => ['required', Rule::in(array_keys($options['sms_sent_systems']))],
            'security_bank_balance' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_drive_balance' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_balance_transfer' => ['required', Rule::in(array_keys($options['on_off']))],
            'security_commission_system' => ['required', Rule::in(array_keys($options['commission_systems']))],
        ];
    }

    protected function validatedSecuritySettingsPayload(Request $request): array
    {
        $validated = $request->validate($this->securitySettingsValidationRules());

        foreach (
            [
                'security_pin_expire_days',
                'security_password_expire_days',
                'security_minimum_pin_length',
                'security_request_interval_minutes',
                'security_session_timeout_minutes',
                'security_bulk_flexi_limit',
                'security_auto_sending_limit',
                'security_daily_limit',
            ] as $field
        ) {
            $validated[$field] = (int) $validated[$field];
        }

        return $validated;
    }

    /**
     * Show Security Modual page.
     */
    public function securityModual()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        $operators = Operator::all();
        $securitySettingsSchemaReady = $this->securitySettingsSchemaReady();
        $securitySettings = $this->resolvedSecuritySettings($settings);
        $securitySettingOptions = $this->securitySettingOptions();
        $pendingCount = $this->pendingRequestCount();
        $totalAmount = 0;
        $totalUsers = 0;
        $today = 0;
        $yesterday = 0;
        $balanceToday = 0;
        $balanceYesterday = 0;
        $operatorSales = collect();
        $bankingSales = collect();

        return view('admin', compact(
            'settings',
            'operators',
            'securitySettings',
            'securitySettingsSchemaReady',
            'securitySettingOptions',
            'pendingCount',
            'totalAmount',
            'totalUsers',
            'today',
            'yesterday',
            'balanceToday',
            'balanceYesterday',
            'operatorSales',
            'bankingSales'
        ));
    }

    /**
     * Show Daily Reports page.
     */
    public function dailyReports(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);
        $pendingCount = $this->pendingRequestCount();
        $selectedDate = (string) $request->query('date', now()->toDateString());

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = now()->toDateString();
        }

        $reportCards = [
            ['title' => 'Flexiload', 'total' => 0],
            ['title' => 'InternetPack', 'total' => 0],
            ['title' => 'Sonali Bank Limited', 'total' => 0],
            ['title' => 'GlobalFlexi', 'total' => 0],
            ['title' => 'BillPay2', 'total' => 0],
            ['title' => 'BPO', 'total' => 0],
        ];

        return view('admin.daily-reports', compact(
            'settings',
            'pendingCount',
            'selectedDate',
            'reportCards'
        ));
    }

    /**
     * Show Sales / Route Report page.
     */
    public function salesReport(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);
        $pendingCount = $this->pendingRequestCount();
        $moduleOptions = [
            '' => '--Any--',
            'flexiload' => 'Flexiload',
            'internet_pack' => 'InternetPack',
        ];
        $defaultDateFrom = '2025-07-01';
        $defaultDateTo = '2026-03-09';

        $selectedModule = (string) $request->query('module', '');
        $selectedSimTo = trim((string) $request->query('sim_to', ''));
        $dateFrom = (string) $request->query('date_from', $defaultDateFrom);
        $dateTo = (string) $request->query('date_to', $defaultDateTo);

        if (! array_key_exists($selectedModule, $moduleOptions)) {
            $selectedModule = '';
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = $defaultDateFrom;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = $defaultDateTo;
        }

        $startDate = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $endDate = \Carbon\Carbon::parse($dateTo)->endOfDay();

        if ($startDate->gt($endDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        $dateFrom = $startDate->toDateString();
        $dateTo = $endDate->toDateString();

        $summaryDefinitions = collect([
            ['key' => 'flexiload', 'label' => 'Flexiload'],
            ['key' => 'internet_pack', 'label' => 'InternetPack'],
        ]);

        if ($selectedModule !== '') {
            $summaryDefinitions = $summaryDefinitions
                ->filter(function (array $definition) use ($selectedModule) {
                    return $definition['key'] === $selectedModule;
                })
                ->values();
        }

        $reportEntries = $this->collectRouteReportEntries($startDate, $endDate, $selectedModule);

        if ($selectedSimTo !== '') {
            $reportEntries = $reportEntries
                ->filter(function (array $entry) use ($selectedSimTo) {
                    return $this->routeReportMatchesSimTo($entry['route_label'] ?? null, $selectedSimTo);
                })
                ->values();
        }

        $summaryRows = $summaryDefinitions
            ->map(function (array $definition) use ($reportEntries) {
                $moduleEntries = $reportEntries->where('module', $definition['key']);

                return [
                    'label' => $definition['label'],
                    'amount' => (float) $moduleEntries->sum('amount'),
                    'qty' => $moduleEntries->count(),
                ];
            })
            ->values();

        $totalAmount = (float) $summaryRows->sum('amount');

        return view('admin.sales-report', compact(
            'settings',
            'pendingCount',
            'moduleOptions',
            'selectedModule',
            'selectedSimTo',
            'dateFrom',
            'dateTo',
            'summaryRows',
            'totalAmount'
        ));
    }

    protected function collectRouteReportEntries($startDate, $endDate, string $selectedModule)
    {
        $finalStatuses = ['approved', 'rejected', 'cancelled'];
        $entries = collect();

        if (($selectedModule === '' || $selectedModule === 'flexiload') && Schema::hasTable('flexi_requests')) {
            $flexiQuery = \DB::table('flexi_requests')->select([
                'flexi_requests.amount',
                $this->routeReportSelectableColumn('flexi_requests', 'is_routed'),
                $this->routeReportSelectableColumn('flexi_requests', 'route_api_id'),
                $this->routeReportSelectableColumn('flexi_requests', 'source_client_domain'),
            ]);

            if (Schema::hasTable('apis') && Schema::hasColumn('flexi_requests', 'route_api_id')) {
                $flexiQuery
                    ->leftJoin('apis', 'flexi_requests.route_api_id', '=', 'apis.id')
                    ->addSelect('apis.title as api_title', 'apis.provider as api_provider');
            } else {
                $flexiQuery
                    ->addSelect(\DB::raw('NULL as api_title'))
                    ->addSelect(\DB::raw('NULL as api_provider'));
            }

            $entries = $entries->concat(
                $flexiQuery
                    ->whereIn('flexi_requests.status', $finalStatuses)
                    ->whereBetween('flexi_requests.created_at', [$startDate, $endDate])
                    ->get()
                    ->map(function ($item) {
                        return [
                            'module' => 'flexiload',
                            'amount' => (float) ($item->amount ?? 0),
                            'route_label' => $this->resolveRouteReportDestinationLabel($item),
                        ];
                    })
            );
        }

        if (($selectedModule === '' || $selectedModule === 'internet_pack') && Schema::hasTable('regular_requests')) {
            $regularQuery = \DB::table('regular_requests')->select([
                'regular_requests.amount',
                $this->routeReportSelectableColumn('regular_requests', 'is_routed'),
                $this->routeReportSelectableColumn('regular_requests', 'route_api_id'),
                $this->routeReportSelectableColumn('regular_requests', 'source_client_domain'),
            ]);

            if (Schema::hasTable('apis') && Schema::hasColumn('regular_requests', 'route_api_id')) {
                $regularQuery
                    ->leftJoin('apis', 'regular_requests.route_api_id', '=', 'apis.id')
                    ->addSelect('apis.title as api_title', 'apis.provider as api_provider');
            } else {
                $regularQuery
                    ->addSelect(\DB::raw('NULL as api_title'))
                    ->addSelect(\DB::raw('NULL as api_provider'));
            }

            $entries = $entries->concat(
                $regularQuery
                    ->whereIn('regular_requests.status', $finalStatuses)
                    ->whereBetween('regular_requests.created_at', [$startDate, $endDate])
                    ->get()
                    ->map(function ($item) {
                        return [
                            'module' => 'internet_pack',
                            'amount' => (float) ($item->amount ?? 0),
                            'route_label' => $this->resolveRouteReportDestinationLabel($item),
                        ];
                    })
            );
        }

        return $entries->values();
    }

    protected function routeReportSelectableColumn(string $table, string $column, ?string $alias = null)
    {
        $alias = $alias ?: $column;

        if (! Schema::hasColumn($table, $column)) {
            return \DB::raw('NULL as ' . $alias);
        }

        if ($alias !== $column) {
            return $table . '.' . $column . ' as ' . $alias;
        }

        return $table . '.' . $column;
    }

    protected function resolveRouteReportDestinationLabel(object $item): string
    {
        $parts = collect([
            trim((string) ($item->api_title ?? '')),
            trim((string) ($item->api_provider ?? '')),
            trim((string) ($item->source_client_domain ?? '')),
        ])
            ->filter()
            ->unique()
            ->values();

        if ($parts->isNotEmpty()) {
            return $parts->implode(' / ');
        }

        if ((bool) ($item->is_routed ?? false) || filled($item->route_api_id ?? null)) {
            return 'Routed API';
        }

        return 'Direct';
    }

    protected function routeReportMatchesSimTo(?string $routeLabel, string $selectedSimTo): bool
    {
        $needle = trim($selectedSimTo);

        if ($needle === '') {
            return true;
        }

        return stripos((string) $routeLabel, $needle) !== false;
    }

    /**
     * Show Operator Reports page.
     */
    public function operatorReports(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);
        $pendingCount = $this->pendingRequestCount();
        $operatorOptions = $this->operatorReportLabels();
        $statusOptions = [
            'success' => 'Success',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ];

        $defaultDateFrom = now()->subMonths(3)->startOfMonth()->toDateString();
        $defaultDateTo = now()->toDateString();

        $selectedOperator = (string) $request->query('operator', '');
        $selectedReseller = (string) $request->query('reseller', '');
        $selectedStatus = (string) $request->query('status', '');
        $dateFrom = (string) $request->query('date_from', $defaultDateFrom);
        $dateTo = (string) $request->query('date_to', $defaultDateTo);

        if (! in_array($selectedOperator, $operatorOptions, true)) {
            $selectedOperator = '';
        }

        if ($selectedStatus !== '' && ! array_key_exists($selectedStatus, $statusOptions)) {
            $selectedStatus = '';
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = $defaultDateFrom;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = $defaultDateTo;
        }

        $startDate = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $endDate = \Carbon\Carbon::parse($dateTo)->endOfDay();

        if ($startDate->gt($endDate)) {
            $endDate = $startDate->copy()->endOfDay();
            $dateTo = $dateFrom;
        }

        $allHistory = $this->collectOperatorReportHistory($startDate, $endDate);

        $resellerOptions = $allHistory
            ->map(function ($item) {
                return (object) [
                    'id' => (string) $item->user_id,
                    'name' => $item->user->name ?? 'N/A',
                ];
            })
            ->unique('id')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if ($selectedReseller !== '') {
            $allHistory = $allHistory->filter(function ($item) use ($selectedReseller) {
                return (string) $item->user_id === $selectedReseller
                    || strcasecmp($item->user->name ?? '', $selectedReseller) === 0;
            })->values();
        }

        if ($selectedStatus !== '') {
            $allHistory = $allHistory->filter(function ($item) use ($selectedStatus) {
                return $item->status === $selectedStatus;
            })->values();
        }

        $groupedTotals = $allHistory->reduce(function ($carry, $item) {
            $label = $this->resolveOperatorReportLabel($item->operator ?? '');

            if ($label === null) {
                return $carry;
            }

            $carry[$label] = ($carry[$label] ?? 0) + (float) ($item->amount ?? 0);

            return $carry;
        }, []);

        $operatorRows = collect($operatorOptions)
            ->map(function ($label, $index) use ($groupedTotals) {
                return [
                    'nr' => $index + 1,
                    'operator' => $label,
                    'amount' => (float) ($groupedTotals[$label] ?? 0),
                ];
            });

        if ($selectedOperator !== '') {
            $operatorRows = $operatorRows
                ->filter(function ($row) use ($selectedOperator) {
                    return $row['operator'] === $selectedOperator;
                })
                ->values()
                ->map(function ($row, $index) {
                    $row['nr'] = $index + 1;

                    return $row;
                });
        }

        $totalAmount = $operatorRows->sum('amount');

        return view('admin.operator-reports', compact(
            'settings',
            'pendingCount',
            'operatorOptions',
            'resellerOptions',
            'statusOptions',
            'selectedOperator',
            'selectedReseller',
            'selectedStatus',
            'dateFrom',
            'dateTo',
            'operatorRows',
            'totalAmount'
        ));
    }

    protected function operatorReportLabels(): array
    {
        return [
            'GrameenPhone',
            'Robi',
            'Banglalink',
            'Airtel',
            'TeleTalk',
            'Citycell',
            'Bkash Personal',
            'bKash Agent',
            'DBBL',
            'Skito',
        ];
    }

    protected function resolveOperatorReportLabel(?string $operator): ?string
    {
        $normalized = strtolower(preg_replace('/[^A-Za-z0-9]+/', '', trim((string) $operator)));

        return match ($normalized) {
            'grameenphone', 'gp' => 'GrameenPhone',
            'robi' => 'Robi',
            'banglalink', 'bl' => 'Banglalink',
            'airtel', 'at' => 'Airtel',
            'teletalk', 'tt' => 'TeleTalk',
            'citycell' => 'Citycell',
            'bkash', 'bkashpersonal', 'personalbkash' => 'Bkash Personal',
            'bkashagent', 'agentbkash' => 'bKash Agent',
            'rocket', 'dbbl', 'dutchbanglabank', 'dutchbanglabanklimited' => 'DBBL',
            'skitto', 'skito' => 'Skito',
            default => null,
        };
    }

    protected function collectOperatorReportHistory($startDate, $endDate)
    {
        $regularRequests = collect();

        if (Schema::hasTable('regular_requests')) {
            $regularRequestsQuery = \DB::table('regular_requests')
                ->join('users', 'regular_requests.user_id', '=', 'users.id')
                ->select('regular_requests.*', 'users.name as user_name', 'users.main_bal');

            if (Schema::hasTable('regular_packages')) {
                $regularRequestsQuery
                    ->leftJoin('regular_packages', 'regular_requests.package_id', '=', 'regular_packages.id')
                    ->addSelect('regular_packages.price as package_price', 'regular_packages.name as package_name');
            } else {
                $regularRequestsQuery
                    ->addSelect(\DB::raw('NULL as package_price'))
                    ->addSelect(\DB::raw('NULL as package_name'));
            }

            $regularRequests = $regularRequestsQuery
                ->whereIn('regular_requests.status', ['approved', 'rejected', 'cancelled'])
                ->whereBetween('regular_requests.created_at', [$startDate, $endDate])
                ->orderBy('regular_requests.created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    $status = 'success';
                    if ($item->status === 'rejected') {
                        $status = 'failed';
                    } elseif ($item->status === 'cancelled') {
                        $status = 'cancelled';
                    }

                    $description = $item->description ?? '';
                    if (empty($description) && ! empty($item->package_name)) {
                        $description = $item->package_name;
                    } elseif (empty($description)) {
                        $description = 'Internet Pack';
                    }

                    return (object) [
                        'id' => $item->id,
                        'user' => (object) ['name' => $item->user_name ?? 'N/A'],
                        'user_id' => $item->user_id,
                        'operator' => $item->operator ?? 'N/A',
                        'mobile' => $item->mobile ?? '-',
                        'amount' => $item->package_price ?? $item->amount ?? 0,
                        'cost' => $item->amount ?? 0,
                        'service' => 'internet',
                        'status' => $status,
                        'original_status' => $item->status,
                        'balance' => $item->main_bal ?? 0,
                        'trnx_id' => null,
                        'description' => $description,
                        'sim_balance' => null,
                        'route' => null,
                        'created_at' => $item->created_at,
                    ];
                });
        }

        $driveHistory = collect();

        if (Schema::hasTable('drive_history')) {
            $driveHistoryQuery = \DB::table('drive_history')
                ->join('users', 'drive_history.user_id', '=', 'users.id')
                ->select('drive_history.*', 'users.name as user_name', 'users.main_bal');

            if (Schema::hasTable('drive_packages')) {
                $driveHistoryQuery
                    ->leftJoin('drive_packages', 'drive_history.package_id', '=', 'drive_packages.id')
                    ->addSelect('drive_packages.price as package_price');
            } else {
                $driveHistoryQuery->addSelect(\DB::raw('NULL as package_price'));
            }

            $driveHistory = $driveHistoryQuery
                ->whereBetween('drive_history.created_at', [$startDate, $endDate])
                ->orderBy('drive_history.created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'id' => $item->id,
                        'user' => (object) ['name' => $item->user_name ?? 'N/A'],
                        'user_id' => $item->user_id,
                        'operator' => $item->operator ?? 'N/A',
                        'mobile' => $item->mobile ?? '-',
                        'amount' => $item->package_price ?? $item->amount ?? 0,
                        'cost' => $item->amount ?? 0,
                        'service' => 'drive',
                        'status' => $item->status ?? 'success',
                        'original_status' => $item->status ?? 'success',
                        'balance' => $item->main_bal ?? 0,
                        'trnx_id' => null,
                        'description' => $item->description,
                        'sim_balance' => null,
                        'route' => null,
                        'created_at' => $item->created_at,
                    ];
                });
        }

        $regularRechargeHistory = Schema::hasTable('recharge_history')
            ? \DB::table('recharge_history')
            ->join('users', 'recharge_history.user_id', '=', 'users.id')
            ->select('recharge_history.*', 'users.name as user_name', 'users.main_bal')
            ->whereBetween('recharge_history.created_at', [$startDate, $endDate])
            ->whereNotLike('recharge_history.type', 'Internet Pack%')
            ->orderBy('recharge_history.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $service = in_array($item->type, ['Bkash', 'Nagad', 'Rocket', 'Upay']) ? 'bkash' : 'regular';

                return (object) [
                    'id' => $item->id,
                    'user' => (object) ['name' => $item->user_name ?? 'N/A'],
                    'user_id' => $item->user_id,
                    'operator' => $item->type ?? 'Regular',
                    'mobile' => '-',
                    'amount' => $item->amount ?? 0,
                    'cost' => $item->amount ?? 0,
                    'service' => $service,
                    'status' => 'success',
                    'original_status' => 'success',
                    'balance' => $item->main_bal ?? 0,
                    'trnx_id' => null,
                    'description' => 'Recharge',
                    'sim_balance' => null,
                    'route' => null,
                    'created_at' => $item->created_at,
                ];
            })
            : collect();

        $flexiHistory = Schema::hasTable('flexi_requests')
            ? \DB::table('flexi_requests')
            ->join('users', 'flexi_requests.user_id', '=', 'users.id')
            ->select('flexi_requests.*', 'users.name as user_name', 'users.main_bal')
            ->whereIn('flexi_requests.status', ['approved', 'rejected', 'cancelled'])
            ->whereBetween('flexi_requests.created_at', [$startDate, $endDate])
            ->orderBy('flexi_requests.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $status = 'success';
                if ($item->status === 'rejected') {
                    $status = 'failed';
                } elseif ($item->status === 'cancelled') {
                    $status = 'cancelled';
                }

                return (object) [
                    'id' => $item->id,
                    'user' => (object) ['name' => $item->user_name ?? 'N/A'],
                    'user_id' => $item->user_id,
                    'operator' => $item->operator ?? 'Flexi',
                    'mobile' => $item->mobile ?? '-',
                    'amount' => $item->amount ?? 0,
                    'cost' => $item->cost ?? $item->amount ?? 0,
                    'service' => 'flexi',
                    'status' => $status,
                    'original_status' => $item->status,
                    'balance' => $item->main_bal ?? 0,
                    'trnx_id' => $item->trnx_id,
                    'description' => trim(($item->type ?? 'Flexi') . ' Flexiload'),
                    'sim_balance' => null,
                    'route' => null,
                    'created_at' => $item->created_at,
                ];
            })
            : collect();

        return $driveHistory
            ->concat($regularRequests)
            ->concat($regularRechargeHistory)
            ->concat($flexiHistory)
            ->sortByDesc('created_at')
            ->values();
    }

    public function updateSecurityModual(Request $request)
    {
        if (! $this->securitySettingsSchemaReady()) {
            return redirect()->route('admin.security.modual')
                ->with('error', 'Security Modual settings columns are not ready. Please run php artisan migrate.');
        }

        HomepageSetting::firstOrCreate([])->update($this->validatedSecuritySettingsPayload($request));

        return redirect()->route('admin.security.modual')
            ->with('success', 'Security Modual settings updated successfully.');
    }

    /**
     * Show service modules page.
     */
    public function serviceModules(Request $request)
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();
        $serviceModuleSchemaReady = Schema::hasTable('service_modules');
        $editingServiceModule = null;

        if ($serviceModuleSchemaReady) {
            $rawServiceModules = ServiceModule::query()
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();

            $editId = $request->query('edit');
            if (preg_match('/^[0-9]+$/', (string) $editId)) {
                $editingServiceModule = ServiceModule::query()->find((int) $editId);
            }
        } else {
            $rawServiceModules = collect($this->defaultServiceModules());
        }

        $serviceModules = collect($rawServiceModules)->map(function ($module) {
            if ($module instanceof ServiceModule) {
                $module = $module->toArray();
            }

            $module['requirements'] = [
                'Pin' => (bool) ($module['require_pin'] ?? false),
                'Name' => (bool) ($module['require_name'] ?? false),
                'NID' => (bool) ($module['require_nid'] ?? false),
                'Sender' => (bool) ($module['require_sender'] ?? false),
            ];

            return $module;
        });

        $pendingCount = $this->pendingRequestCount();
        $totalAmount = 0;
        $totalUsers = 0;
        $today = 0;
        $yesterday = 0;
        $balanceToday = 0;
        $balanceYesterday = 0;
        $operatorSales = collect();
        $bankingSales = collect();

        return view('admin', compact('settings', 'operators', 'serviceModules', 'editingServiceModule', 'serviceModuleSchemaReady', 'pendingCount', 'totalAmount', 'totalUsers', 'today', 'yesterday', 'balanceToday', 'balanceYesterday', 'operatorSales', 'bankingSales'));
    }

    public function storeServiceModule(Request $request)
    {
        if (! Schema::hasTable('service_modules')) {
            return redirect()->route('admin.service.modules')
                ->with('error', 'Service Modules table not ready. Please run php artisan migrate.');
        }

        ServiceModule::query()->create($this->validatedServiceModulePayload($request));

        return redirect()->route('admin.service.modules')
            ->with('success', 'Service module created successfully.');
    }

    public function updateServiceModule(Request $request, $serviceModule)
    {
        if (! Schema::hasTable('service_modules')) {
            return redirect()->route('admin.service.modules')
                ->with('error', 'Service Modules table not ready. Please run php artisan migrate.');
        }

        $serviceModule = $this->findServiceModuleOrFail($serviceModule);
        $serviceModule->update($this->validatedServiceModulePayload($request, $serviceModule));

        return redirect()->route('admin.service.modules')
            ->with('success', 'Service module updated successfully.');
    }

    public function rechargeBlockList(Request $request)
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();
        $rechargeBlockListSchemaReady = Schema::hasTable('recharge_block_lists');
        $rechargeBlockLists = collect();
        $rechargeBlockServiceOptions = $this->rechargeBlockServiceOptions();
        $rechargeBlockOperatorOptions = $this->rechargeBlockOperatorOptions($operators);

        if ($rechargeBlockListSchemaReady) {
            $rechargeBlockLists = RechargeBlockList::query()
                ->orderBy('service')
                ->orderBy('operator')
                ->orderBy('amount')
                ->get();
        }

        $pendingCount = $this->pendingRequestCount();
        $totalAmount = 0;
        $totalUsers = 0;
        $today = 0;
        $yesterday = 0;
        $balanceToday = 0;
        $balanceYesterday = 0;
        $operatorSales = collect();
        $bankingSales = collect();

        return view('admin', compact(
            'settings',
            'operators',
            'rechargeBlockListSchemaReady',
            'rechargeBlockLists',
            'rechargeBlockServiceOptions',
            'rechargeBlockOperatorOptions',
            'pendingCount',
            'totalAmount',
            'totalUsers',
            'today',
            'yesterday',
            'balanceToday',
            'balanceYesterday',
            'operatorSales',
            'bankingSales'
        ));
    }

    public function storeRechargeBlockList(Request $request)
    {
        if (! Schema::hasTable('recharge_block_lists')) {
            return redirect()->route('admin.recharge.block.list')
                ->with('error', 'Recharge Block List table not ready. Please run php artisan migrate.');
        }

        $payload = $this->validatedRechargeBlockListPayload($request);

        $alreadyExists = RechargeBlockList::query()
            ->where('service', $payload['service'])
            ->where('operator', $payload['operator'])
            ->where('amount', $payload['amount'])
            ->exists();

        if ($alreadyExists) {
            return redirect()->route('admin.recharge.block.list')
                ->with('error', 'This recharge block entry already exists.');
        }

        RechargeBlockList::query()->create($payload);

        return redirect()->route('admin.recharge.block.list')
            ->with('success', 'Recharge block entry created successfully.');
    }

    public function destroyRechargeBlockList($rechargeBlockList)
    {
        if (! Schema::hasTable('recharge_block_lists')) {
            return redirect()->route('admin.recharge.block.list')
                ->with('error', 'Recharge Block List table not ready. Please run php artisan migrate.');
        }

        $rechargeBlockList = $this->findRechargeBlockListOrFail($rechargeBlockList);
        $rechargeBlockList->delete();

        return redirect()->route('admin.recharge.block.list')
            ->with('success', 'Recharge block entry deleted successfully.');
    }

    /**
     * Show all history.
     */
    public function allHistory(Request $request)
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();
        // Get filter parameters
        $show = $request->query('show', 50);
        $number = $request->query('number');
        $reseller = $request->query('reseller');
        $service = $request->query('service');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Show all history by default; only narrow results when a date filter is provided
        if (empty($dateFrom) && empty($dateTo)) {
            $startDate = now()->subYears(20)->startOfDay();
            $endDate = now()->endOfDay();
        } else {
            $startDate = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : now()->subYear()->startOfDay();
            $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();
        }

        // Get all regular requests (approved, rejected, cancelled) with mobile and operator info
        $regularRequests = \App\Models\RegularRequest::with(['user', 'package'])
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

                // Get description - use admin-entered description if available, otherwise use package name
                $description = $item->description ?? '';
                if (empty($description) && $item->package) {
                    $description = $item->package->name ?? 'Internet Pack';
                } elseif (empty($description)) {
                    $description = 'Internet Pack';
                }

                // Amount = Offer Price (original), Cost = Main Price (user paid)
                $offerPrice = $item->package ? ($item->package->price ?? 0) : ($item->amount ?? 0);
                $mainPrice = $item->amount ?? 0;

                return (object) [
                    'id' => $item->id,
                    'user' => (object)['name' => $item->user->name ?? 'N/A'],
                    'user_id' => $item->user_id,
                    'operator' => $item->operator ?? 'N/A',
                    'mobile' => $item->mobile ?? '-',
                    'amount' => $offerPrice,  // Offer Price (original)
                    'cost' => $mainPrice,  // Main Price (user paid)
                    'service' => 'internet',
                    'status' => $status,
                    'original_status' => $item->status,
                    'balance' => $item->user->main_bal ?? 0,
                    'trnx_id' => null,
                    'description' => $description,
                    'sim_balance' => null,
                    'route' => null,
                    'created_at' => $item->created_at,
                ];
            });

        // Get drive history with date filter
        $driveHistory = \DB::table('drive_history')
            ->join('users', 'drive_history.user_id', '=', 'users.id')
            ->leftJoin('drive_packages', 'drive_history.package_id', '=', 'drive_packages.id')
            ->select('drive_history.*', 'users.name as user_name', 'users.main_bal', 'users.email as user_email', 'drive_packages.price as package_price')
            ->whereBetween('drive_history.created_at', [$startDate, $endDate])
            ->orderBy('drive_history.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                // Amount = Offer Price (original), Cost = Main Price (user paid)
                $offerPrice = $item->package_price ?? $item->amount;
                $mainPrice = $item->amount;

                return (object) [
                    'id' => $item->id,
                    'user' => (object)['name' => $item->user_name],
                    'user_id' => $item->user_id,
                    'operator' => $item->operator,
                    'mobile' => $item->mobile,
                    'amount' => $offerPrice,  // Offer Price (original)
                    'cost' => $mainPrice,  // Main Price (user paid)
                    'service' => 'drive',
                    'status' => $item->status,
                    'original_status' => $item->status,
                    'balance' => $item->main_bal,
                    'trnx_id' => null,
                    'description' => $item->description,
                    'sim_balance' => null,
                    'route' => null,
                    'created_at' => $item->created_at,
                ];
            });

        // Get regular recharge history (Bkash, Nagad, etc. - excluding Internet Pack which is in regular_requests)
        $regularRechargeHistory = \DB::table('recharge_history')
            ->join('users', 'recharge_history.user_id', '=', 'users.id')
            ->select('recharge_history.*', 'users.name as user_name', 'users.main_bal', 'users.email as user_email')
            ->whereBetween('recharge_history.created_at', [$startDate, $endDate])
            ->whereNotLike('recharge_history.type', 'Internet Pack%')
            ->orderBy('recharge_history.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $service = 'regular';
                if (in_array($item->type, ['Bkash', 'Nagad', 'Rocket', 'Upay'])) {
                    $service = 'bkash';
                }

                return (object) [
                    'id' => $item->id,
                    'user' => (object) ['name' => $item->user_name],
                    'user_id' => $item->user_id,
                    'operator' => $item->type ?? 'Regular',
                    'mobile' => '-',
                    'amount' => $item->amount,
                    'cost' => $item->amount,  // Same as amount for recharge
                    'service' => $service,
                    'status' => 'success',
                    'original_status' => 'success',
                    'balance' => $item->main_bal,
                    'trnx_id' => null,
                    'description' => 'Recharge',
                    'sim_balance' => null,
                    'route' => null,
                    'created_at' => $item->created_at,
                ];
            });

        $flexiHistory = Schema::hasTable('flexi_requests')
            ? \App\Models\FlexiRequest::with('user')
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
                    'id' => $item->id,
                    'user' => (object) ['name' => $item->user->name ?? 'N/A'],
                    'user_id' => $item->user_id,
                    'operator' => $item->operator ?? 'Flexi',
                    'mobile' => $item->mobile ?? '-',
                    'amount' => $item->amount ?? 0,
                    'cost' => $item->cost ?? $item->amount ?? 0,
                    'service' => 'flexi',
                    'status' => $status,
                    'original_status' => $item->status,
                    'balance' => $item->user->main_bal ?? 0,
                    'trnx_id' => $item->trnx_id,
                    'description' => trim(($item->type ?? 'Flexi') . ' Flexiload'),
                    'sim_balance' => null,
                    'route' => null,
                    'created_at' => $item->created_at,
                ];
            })
            : collect();

        // Combine all history
        $allHistory = $driveHistory
            ->concat($regularRequests)
            ->concat($regularRechargeHistory)
            ->concat($flexiHistory)
            ->sortByDesc('created_at')
            ->values();

        // Apply additional filters
        if ($number) {
            $allHistory = $allHistory->filter(function ($item) use ($number) {
                return stripos($item->mobile, $number) !== false || stripos($item->operator, $number) !== false;
            })->values();
        }

        if ($reseller) {
            $allHistory = $allHistory->filter(function ($item) use ($reseller) {
                return stripos($item->user->name, $reseller) !== false;
            })->values();
        }

        if ($service) {
            $allHistory = $allHistory->filter(function ($item) use ($service) {
                return $item->service === $service;
            })->values();
        }

        if ($status) {
            $allHistory = $allHistory->filter(function ($item) use ($status) {
                return $item->status === $status;
            })->values();
        }

        // Calculate totals before pagination
        $totalAmount = $allHistory->sum('amount');
        $totalCost = $allHistory->sum(function ($item) {
            return $item->cost ?? 0;
        });

        // Apply pagination
        $allHistory = $allHistory->take($show);

        $pendingCount = \App\Models\DriveRequest::where('status', 'pending')->count()
            + \App\Models\RegularRequest::where('status', 'pending')->count();
        $totalUsers = 0;
        $today = 0;
        $yesterday = 0;
        $balanceToday = 0;
        $balanceYesterday = 0;
        $operatorSales = collect();
        $bankingSales = collect();

        return view('admin', compact('settings', 'operators', 'allHistory', 'pendingCount', 'totalAmount', 'totalCost', 'totalUsers', 'today', 'yesterday', 'balanceToday', 'balanceYesterday', 'operatorSales', 'bankingSales', 'show', 'number', 'reseller', 'service', 'status', 'dateFrom', 'dateTo'));
    }

    /**
     * Show internet pack (regular) history only.
     */
    public function internetHistory(Request $request)
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();

        // Get filter dates (default to today only)
        $dateFilter = $request->query('date', 'today');

        if ($dateFilter === 'today') {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($dateFilter === 'yesterday') {
            $startDate = now()->subDay()->startOfDay();
            $endDate = now()->subDay()->endOfDay();
        } elseif ($dateFilter === 'week') {
            $startDate = now()->subWeek()->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($dateFilter === 'month') {
            $startDate = now()->subMonth()->startOfDay();
            $endDate = now()->endOfDay();
        } else {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        }

        // Get all regular requests (Internet Pack) - sorted by latest ID first
        $internetHistory = \App\Models\RegularRequest::with(['user', 'package'])
            ->whereIn('status', ['approved', 'rejected', 'cancelled'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $status = 'success';
                if ($item->status === 'rejected') {
                    $status = 'failed';
                } elseif ($item->status === 'cancelled') {
                    $status = 'cancelled';
                }

                // Get description - use admin-entered description if available, otherwise use package name
                $description = $item->description ?? '';
                if (empty($description) && $item->package) {
                    $description = $item->package->name ?? 'Internet Pack';
                } elseif (empty($description)) {
                    $description = 'Internet Pack';
                }

                return (object) [
                    'id' => $item->id,
                    'user' => (object)['name' => $item->user->name ?? 'N/A'],
                    'operator' => $item->operator ?? 'N/A',
                    'mobile' => $item->mobile ?? '-',
                    'amount' => $item->amount ?? 0,
                    'cost' => $item->amount ?? 0,
                    'service' => 'internet',
                    'status' => $status,
                    'balance' => $item->user->main_bal ?? 0,
                    'trnx_id' => null,
                    'description' => $description,
                    'sim_balance' => null,
                    'route' => null,
                    'created_at' => $item->created_at,
                ];
            });

        // Calculate totals
        $totalAmount = $internetHistory->sum('amount');

        $pendingCount = \App\Models\DriveRequest::where('status', 'pending')->count()
            + \App\Models\RegularRequest::where('status', 'pending')->count();
        $totalUsers = 0;
        $today = 0;
        $yesterday = 0;
        $balanceToday = 0;
        $balanceYesterday = 0;
        $operatorSales = collect();
        $bankingSales = collect();

        return view('admin', compact('settings', 'operators', 'internetHistory', 'pendingCount', 'totalAmount', 'totalUsers', 'today', 'yesterday', 'balanceToday', 'balanceYesterday', 'operatorSales', 'bankingSales', 'dateFilter'));
    }

    public function storeDriveOffer(Request $request)
    {
        $validated = $request->validate([
            'operator' => 'required|string',
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0',
            'expire' => 'required|date',
            'status' => 'required|in:active,deactive'
        ]);

        DrivePackage::create([
            'operator' => $validated['operator'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'commission' => $validated['commission'],
            'expire' => $validated['expire'],
            'status' => $validated['status'],
        ]);

        $this->notifyAllUsers(
            'New Drive Offer Added',
            $validated['operator'] . ' - ' . $validated['name'] . ' is now available.',
            route('user.drive'),
        );

        return back()->with('success', 'Drive Package added successfully!');
    }

    public function storeRegularOffer(Request $request)
    {
        $validated = $request->validate([
            'operator' => 'required|string',
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0',
            'expire' => 'required|date',
            'status' => 'required|in:active,deactive'
        ]);

        RegularPackage::create([
            'operator' => $validated['operator'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'commission' => $validated['commission'],
            'expire' => $validated['expire'],
            'status' => $validated['status'],
        ]);

        $this->notifyAllUsers(
            'New Internet Offer Added',
            $validated['operator'] . ' - ' . $validated['name'] . ' is now available.',
            route('user.internet'),
        );

        return back()->with('success', 'Regular Package added successfully!');
    }

    public function store(Request $request, $operator)
    {
        $package = new \App\Models\DrivePackage();
        $package->operator = $operator;
        $package->name = $request->name;
        $package->price = $request->price;
        $package->commission = $request->commission ?? 0;

        $package->selling_price = $request->price - ($request->commission ?? 0);


        $package->expire = $request->expire;

        $package->status = strtolower($request->status);

        $package->save();

        return back()->with('success', 'Package added successfully!');
    }
    // ফর্ম দেখানোর জন্য
    // ১১০৪ নম্বর লাইনে ফাংশনের নাম পরিবর্তন করুন
    public function storeOperator(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:operators,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $operator = new \App\Models\Operator();
        $operator->name = $request->name;
        // লোগো সেভ করার লজিক এখানে যুক্ত করতে পারেন
        $operator->save();

        return back()->with('success', 'Operator added successfully!');
    }


    public function createOperator()
    {

        $settings = \App\Models\HomepageSetting::first();


        $operatorSales = collect([]);
        $bankingSales = collect([]);

        return view('admin.operator.create', compact('settings', 'operatorSales', 'bankingSales'));
    }

    /**
     * Delete all history data.
     */
    public function deleteAllHistory(Request $request)
    {
        // Delete all records from drive_history
        \DB::table('drive_history')->truncate();

        // Delete all records from recharge_history
        \DB::table('recharge_history')->truncate();

        // Delete all records from regular_requests
        \App\Models\RegularRequest::truncate();

        return redirect()->route('admin.all.history')->with('success', 'All history data deleted successfully!');
    }
    /**
     * Show all operators to manage logos/details.
     */
    public function manageOperators()
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();
        return view('admin.manage-operators', compact('settings', 'operators'));
    }

    /**
     * Update operator logo and info.
     */
    public function updateOperator(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:1024',
        ]);

        $operator = Operator::findOrFail($id);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($operator->logo) {
                \Storage::disk('public')->delete($operator->logo);
            }

            // Store new logo
            $path = $request->file('logo')->store('operators', 'public');
            $operator->logo = $path;
        }

        $operator->name = $request->name;
        $operator->save();

        return redirect()->back()->with('success', 'Operator updated successfully!');
    }
}
