<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeController extends Controller
{
    /**
    
     */
    public function index()
    {
        $notice = DB::table('site_notices')->where('id', 1)->first();
        $settings = HomepageSetting::first();

        return view('admin.notice', compact('notice', 'settings'));
    }

    /**
     
     */
    public function update(Request $request)
    {

        $request->validate([
            'notice_text' => 'required|string',
        ]);


        DB::table('site_notices')->updateOrInsert(
            ['id' => 1],
            [
                'notice_text' => $request->notice_text,
                'updated_at' => now()
            ]
        );

        return redirect()->back()->with('success', 'Notice updated successfully!');
    }
}
