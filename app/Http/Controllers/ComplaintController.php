<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Services\SecurityRuntimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    // User: View complaints
    public function index(Request $request)
    {
        $securityRuntime = app(SecurityRuntimeService::class);

        if (! $securityRuntime->isSupportTicketEnabled()) {
            return redirect()
                ->route('dashboard')
                ->with('error', $securityRuntime->supportTicketDisabledMessage());
        }

        $settings = \App\Models\HomepageSetting::first();
        $user = auth()->user();

        $pendingDriveRequests = \App\Models\DriveRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        $pendingRegularRequests = \App\Models\RegularRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        $pendingCount = $pendingDriveRequests + $pendingRegularRequests;

        $query = Complaint::query();

        if ($request->filled('complaint_id')) {
            $query->where('id', $request->complaint_id);
        }

        if ($request->filled('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%')
                ->orWhere('message', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status') && $request->status != '--Any--') {
            $query->where('status', $request->status);
        }

        $complaints = $query->where('sender_email', Auth::user()->email)
            ->latest()
            ->get();

        return view('complaints', compact('complaints', 'settings', 'user', 'pendingCount'));
    }

    // User: Submit new complaint
    public function store(Request $request)
    {
        $securityRuntime = app(SecurityRuntimeService::class);

        if (! $securityRuntime->isSupportTicketEnabled()) {
            return redirect()
                ->route('dashboard')
                ->with('error', $securityRuntime->supportTicketDisabledMessage());
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            // Generate complaint number (COMP + YearMonthDay + Random 4 digits)
            $complaintNumber = 'COMP' . date('Ymd') . rand(1000, 9999);

            Complaint::create([
                'complaint_number' => $complaintNumber,
                'subject'      => $request->subject,
                'message'      => $request->message,
                'sender_email' => Auth::user()->email,
                'status'       => 'Open',
            ]);

            return back()->with('success', 'Your complaint has been submitted successfully. Complaint Number: ' . $complaintNumber);
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    // Admin: View all complaints
    public function adminIndex(Request $request)
    {
        $settings = \App\Models\HomepageSetting::first();

        $query = Complaint::query();

        if ($request->filled('complaint_id')) {
            $query->where('id', $request->complaint_id);
        }

        if ($request->filled('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%')
                ->orWhere('message', 'like', '%' . $request->search . '%')
                ->orWhere('sender_email', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status') && $request->status != '--Any--') {
            $query->where('status', $request->status);
        }

        $complaints = $query->latest()->get();

        return view('admin.complaints', compact('complaints', 'settings'));
    }

    // Admin: Reply to complaint
    public function adminReply(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string',
            'status' => 'required|string',
        ]);

        try {
            $complaint = Complaint::findOrFail($id);
            $complaint->reply = $request->reply;
            $complaint->status = $request->status;
            $complaint->save();

            return back()->with('success', 'Reply sent successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }
}
