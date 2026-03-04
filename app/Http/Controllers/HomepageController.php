<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
use App\Models\Operator;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function index()
    {
        $settings = HomepageSetting::first();

        $operators = Operator::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('welcome', compact('settings', 'operators'));
    }

    public function edit()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        $operators = Operator::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.general-settings', compact('settings', 'operators'));
    }

    public function update(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);

        $data = $request->only([
            'page_title',
            'company_name',
            'operators_title',
            'operators_subtitle',
            'features_title',
            'features_subtitle',
            'feature1_title',
            'feature1_description',
            'feature2_title',
            'feature2_description',
            'feature3_title',
            'feature3_description',
            'feature4_title',
            'feature4_description',
            'stats_customers_label',
            'stats_customers_value',
            'stats_recharged_label',
            'stats_recharged_value',
            'stats_operators_label',
            'stats_operators_value',
            'stats_service_label',
            'stats_service_value',
            'footer_company_name',
            'footer_description',
            'footer_address',
            'footer_phone',
            'footer_email',
            'social_whatsapp_url',
            'social_youtube_url',
            'social_shopee_url',
            'social_telegram_url',
            'social_messenger_url',
        ]);

        if ($request->hasFile('company_logo')) {
            $logo = $request->file('company_logo');
            $logoName = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('uploads'), $logoName);
            $data['company_logo_url'] = 'uploads/' . $logoName;
        }

        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon');
            $faviconName = 'favicon_' . time() . '.' . $favicon->getClientOriginalExtension();
            $favicon->move(public_path('uploads'), $faviconName);
            $data['favicon_path'] = 'uploads/' . $faviconName;
        }

        $settings->update($data);

        return redirect()
            ->route('admin.homepage.edit')
            ->with('success', 'Settings updated successfully.');
    }

    public function mailConfig()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        return view('admin.mail-config', compact('settings'));
    }

    public function updateMailConfig(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);

        $data = $request->only([
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ]);

        $settings->update($data);

        return redirect()
            ->route('admin.mail.config')
            ->with('success', 'Mail settings updated successfully.');
    }

    public function smsConfig()
    {
        $settings = HomepageSetting::firstOrCreate([]);
        return view('admin.sms-config', compact('settings'));
    }

    public function updateSmsConfig(Request $request)
    {
        $settings = HomepageSetting::firstOrCreate([]);

        $data = $request->only([
            'sms_api_key',
            'sms_sender_id',
            'sms_api_url',
        ]);

        $settings->update($data);

        return redirect()
            ->route('admin.sms.config')
            ->with('success', 'SMS settings updated successfully.');
    }
}

