<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\HomepageSetting;
use App\Models\Operator;
use App\Models\DrivePackage;
use App\Models\RegularPackage;
use Illuminate\Http\Request;

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
        
        // Pending drive requests count
        $pendingCount = \App\Models\DriveRequest::where('status', 'pending')->count()
            + \App\Models\RegularRequest::where('status', 'pending')->count();
        
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
            'amount' => ['required', 'numeric', 'min:0.01']
        ]);

        $user = User::findOrFail($userId);
        $balanceType = $validated['balance_type'];
        $user->$balanceType += $validated['amount'];
        $user->save();
        
        // Refresh the user to ensure database changes are loaded
        $user->refresh();

        // Record balance addition in history
        $balanceTypeName = match($balanceType) {
            'main_bal' => 'Main Balance',
            'drive_bal' => 'Drive Balance',
            'bank_bal' => 'Bank Balance',
            default => 'Balance'
        };
        
        \DB::table('balance_add_history')->insert([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'type' => $balanceTypeName,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('admin.all.resellers')->with('success', 'Balance added successfully!');
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
            'amount' => ['required', 'numeric', 'min:0.01']
        ]);

        $user = User::findOrFail($userId);
        $balanceType = $validated['balance_type'];
        
        // Check if user has enough balance
        if ($user->$balanceType < $validated['amount']) {
            return redirect()->back()->with('error', 'Insufficient balance!');
        }
        
        $user->$balanceType -= $validated['amount'];
        $user->save();

        // Record balance deduction in history
        $balanceTypeName = match($balanceType) {
            'main_bal' => 'Main Balance',
            'drive_bal' => 'Drive Balance',
            'bank_bal' => 'Bank Balance',
            default => 'Balance'
        };
        
        \DB::table('balance_add_history')->insert([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'type' => 'Returned: ' . $balanceTypeName,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('admin.all.resellers')->with('success', 'Balance returned successfully!');
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
        $pendingCount = \App\Models\DriveRequest::where('status', 'pending')->count()
            + \App\Models\RegularRequest::where('status', 'pending')->count();

        return view('admin', compact('users', 'settings', 'operators', 'level', 'username', 'status', 'pendingCount'));
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'pin' => ['required', 'digits:4'],
            'level' => ['required', 'in:house,dgm,dealer,seller,retailer'],
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
        ]);

        return redirect()->route('admin.resellers')->with('success', 'User created successfully.');
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
        
        $callback = function() use ($users) {
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
    public function profile()
    {
        $settings = HomepageSetting::first();
        $admin = auth()->user();
        return view('admin.profile', compact('settings', 'admin'));
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
        $validated = $request->validate([
            'new_password' => ['required', 'min:6', 'confirmed'],
        ]);

        auth()->user()->update(['password' => \Hash::make($validated['new_password'])]);

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

        auth()->user()->update(['pin' => \Hash::make($validated['new_pin'])]);

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
            ->when(request('search'), function($query) {
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

        return response()->json(['success' => true]);
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
            ->when(request('search'), function($query) {
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

        return response()->json(['success' => true]);
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
    public function pendingDriveRequests()
    {
        $settings = HomepageSetting::first();
        $operators = Operator::all();
        $driveRequests = \App\Models\DriveRequest::where('status', 'pending')
            ->with(['user', 'package'])
            ->get()
            ->map(function ($item) {
                $item->request_type = 'Drive';
                return $item;
            });

        $regularRequests = \App\Models\RegularRequest::where('status', 'pending')
            ->with(['user', 'package'])
            ->get()
            ->map(function ($item) {
                $item->request_type = 'Internet';
                return $item;
            });

        $requests = $driveRequests
            ->concat($regularRequests)
            ->sortByDesc('created_at')
            ->values();
        $pendingCount = $requests->count();
        $totalAmount = 0;
        $totalUsers = 0;
        $today = 0;
        $yesterday = 0;
        $balanceToday = 0;
        $balanceYesterday = 0;
        $operatorSales = collect();
        $bankingSales = collect();
        return view('admin', compact('settings', 'operators', 'requests', 'pendingCount', 'totalAmount', 'totalUsers', 'today', 'yesterday', 'balanceToday', 'balanceYesterday', 'operatorSales', 'bankingSales'));
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
            'pin' => 'required'
        ]);

        if (!\Hash::check($validated['pin'], auth()->user()->pin)) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $driveRequest = \App\Models\DriveRequest::findOrFail($id);
        
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
            'description' => $validated['description'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

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

        if (!\Hash::check($validated['pin'], auth()->user()->pin)) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $driveRequest = \App\Models\DriveRequest::findOrFail($id);
        $driveRequest->update(['status' => 'rejected']);
        
        // Refund user's drive balance
        $user = $driveRequest->user;
        $user->drive_bal += $driveRequest->amount;
        $user->save();
        
        // Add to drive history
        \DB::table('drive_history')->insert([
            'user_id' => $driveRequest->user_id,
            'package_id' => $driveRequest->package_id,
            'operator' => $driveRequest->operator,
            'mobile' => $driveRequest->mobile,
            'amount' => $driveRequest->amount,
            'status' => 'failed',
            'description' => $validated['description'] ?? 'Request failed by admin',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return redirect()->route('admin.pending.drive.requests')->with('success', 'Request failed and balance refunded!');
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

        if (!\Hash::check($validated['pin'], auth()->user()->pin)) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $driveRequest = \App\Models\DriveRequest::findOrFail($id);
        $driveRequest->update(['status' => 'cancelled']);
        
        // Refund user's drive balance
        $user = $driveRequest->user;
        $user->drive_bal += $driveRequest->amount;
        $user->save();
        
        return redirect()->route('admin.pending.drive.requests')->with('success', 'Request cancelled and balance refunded!');
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
            'pin' => 'required'
        ]);

        if (!\Hash::check($validated['pin'], auth()->user()->pin)) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $regularRequest = \App\Models\RegularRequest::findOrFail($id);
        
        // Update status and save the admin-entered description
        $regularRequest->update([
            'status' => 'approved',
            'description' => $validated['description'] ?? 'Success'
        ]);

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

        if (!\Hash::check($validated['pin'], auth()->user()->pin)) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $regularRequest = \App\Models\RegularRequest::with('user')->findOrFail($id);
        $regularRequest->update(['status' => 'rejected']);

        $user = $regularRequest->user;
        $user->main_bal += $regularRequest->amount;
        $user->save();

        return redirect()->route('admin.pending.drive.requests')->with('success', 'Regular request failed and balance refunded!');
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

        if (!\Hash::check($validated['pin'], auth()->user()->pin)) {
            return redirect()->back()->with('error', 'Invalid PIN!');
        }

        $regularRequest = \App\Models\RegularRequest::with('user')->findOrFail($id);
        $regularRequest->update(['status' => 'rejected']);

        $user = $regularRequest->user;
        $user->main_bal += $regularRequest->amount;
        $user->save();

        return redirect()->route('admin.pending.drive.requests')->with('success', 'Regular request cancelled and balance refunded!');
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
            ->map(function($item) {
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
        
        // Default to today if no date range is provided
        if (empty($dateFrom) && empty($dateTo)) {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        } else {
            $startDate = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : now()->subYear()->startOfDay();
            $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();
        }

        // Get all regular requests (approved, rejected, cancelled) with mobile and operator info
        $regularRequests = \App\Models\RegularRequest::with(['user', 'package'])
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
                
                // Cost = Original package price (offer price), Amount = User paid (main price)
                $cost = $item->package ? ($item->package->price ?? 0) : ($item->amount ?? 0);
                
                return (object) [
                    'id' => $item->id,
                    'user' => (object)['name' => $item->user->name ?? 'N/A'],
                    'user_id' => $item->user_id,
                    'operator' => $item->operator ?? 'N/A',
                    'mobile' => $item->mobile ?? '-',
                    'amount' => $item->amount ?? 0,  // Main Price (user paid)
                    'cost' => $cost,  // Offer Price (original price)
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
            ->map(function($item) {
                // Cost = Original package price (offer price), Amount = User paid (main price)
                $cost = $item->package_price ?? $item->amount;
                
                return (object) [
                    'id' => $item->id,
                    'user' => (object)['name' => $item->user_name],
                    'user_id' => $item->user_id,
                    'operator' => $item->operator,
                    'mobile' => $item->mobile,
                    'amount' => $item->amount,  // Main Price (user paid)
                    'cost' => $cost,  // Offer Price (original price)
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

        // Combine all history
        $allHistory = $driveHistory
            ->concat($regularRequests)
            ->concat($regularRechargeHistory)
            ->sortByDesc('created_at')
            ->values();

        // Apply additional filters
        if ($number) {
            $allHistory = $allHistory->filter(function($item) use ($number) {
                return stripos($item->mobile, $number) !== false || stripos($item->operator, $number) !== false;
            })->values();
        }
        
        if ($reseller) {
            $allHistory = $allHistory->filter(function($item) use ($reseller) {
                return stripos($item->user->name, $reseller) !== false;
            })->values();
        }
        
        if ($service) {
            $allHistory = $allHistory->filter(function($item) use ($service) {
                return $item->service === $service;
            })->values();
        }
        
        if ($status) {
            $allHistory = $allHistory->filter(function($item) use ($status) {
                return $item->status === $status;
            })->values();
        }
        
        // Calculate totals before pagination
        $totalAmount = $allHistory->sum('amount');
        $totalCost = $allHistory->sum(function($item) {
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

        // Get all regular requests (Internet Pack) - sorted by ID ascending (oldest first, newest at bottom - SL 1)
        $internetHistory = \App\Models\RegularRequest::with(['user', 'package'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('id', 'asc')
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
}

