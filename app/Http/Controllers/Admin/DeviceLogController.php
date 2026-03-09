<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceLog;
use App\Models\User; // Reseller list fetch korar jonno
use App\Models\HomepageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DeviceLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DeviceLog::query();

        // ১. Reseller/User Filter logic
        if ($request->filled('reseller')) {
            $query->where('username', $request->reseller);
        }

        // ২. Pagination handling (10, 50, 100 etc.)
        $perPage = $request->get('per_page', 10);

        // ৩. Fetch data with pagination
        $logs = $query->latest()->paginate($perPage)->withQueryString();

        // ৪. Reseller dropdown-er jonno user list (Example: level onujayi)
        $resellerQuery = User::query()->select('id', 'name', 'email');

        if (Schema::hasColumn('users', 'username')) {
            $resellerQuery->addSelect('username');
        }

        $resellers = $resellerQuery->get();

        // Get settings for company name display
        $settings = HomepageSetting::first();

        // Get current authenticated user for profile picture
        $user = auth()->user();

        return view('admin.operator.device-logs', compact('logs', 'resellers', 'settings', 'user'));
    }

    public function approve($id)
    {
        $log = DeviceLog::findOrFail($id);
        $log->update([
            'status' => 'active',
            'two_step_verified' => true,
        ]);

        return back()->with('success', 'Device approved successfully!');
    }

    public function destroy($id)
    {
        $log = DeviceLog::findOrFail($id);
        $log->delete();

        return back()->with('success', 'Log deleted successfully!');
    }
}
