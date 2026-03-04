<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\HomepageSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class OtpService
{
    public function generateOtp()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function sendOtp($identifier, $type, $channel = 'email')
    {
        $this->configureMailFromDatabase();
        $otpCode = $this->generateOtp();

        if ($channel === 'email') {
            Otp::where('email', $identifier)->where('type', $type)->delete();
        } else {
            Otp::where('mobile', $identifier)->where('type', $type)->delete();
        }

        Otp::create([
            'email' => $channel === 'email' ? $identifier : null,
            'mobile' => $channel === 'sms' ? $identifier : null,
            'otp' => $otpCode,
            'type' => $type,
            'channel' => $channel,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);

        try {
            if ($channel === 'email') {
                Mail::raw("Your OTP code is: {$otpCode}\n\nThis code will expire in 10 minutes.", function ($message) use ($identifier, $type) {
                    $message->to($identifier)->subject($type === 'registration' ? 'Registration OTP' : 'Password Reset OTP');
                });
            } else {
                // SMS sending logic here (integrate with SMS gateway)
                // For now, just log it
                \Log::info("SMS OTP to {$identifier}: {$otpCode}");
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function verifyOtp($identifier, $otp, $type, $channel = 'email')
    {
        $query = Otp::where('otp', $otp)
            ->where('type', $type)
            ->where('channel', $channel)
            ->where('is_used', false);

        if ($channel === 'email') {
            $query->where('email', $identifier);
        } else {
            $query->where('mobile', $identifier);
        }

        $otpRecord = $query->first();

        if (!$otpRecord || $otpRecord->isExpired()) {
            return false;
        }

        $otpRecord->update(['is_used' => true]);
        return true;
    }

    private function configureMailFromDatabase()
    {
        $settings = HomepageSetting::first();

        if ($settings && $settings->mail_host) {
            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => $settings->mail_host,
                'port' => $settings->mail_port ?? 587,
                'encryption' => $settings->mail_encryption ?? 'tls',
                'username' => $settings->mail_username,
                'password' => $settings->mail_password,
            ]);

            Config::set('mail.from', [
                'address' => $settings->mail_from_address ?? 'noreply@example.com',
                'name' => $settings->mail_from_name ?? 'Codecartel Telecom',
            ]);
        }
    }
}
