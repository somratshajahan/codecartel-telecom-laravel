<?php

namespace App\Services;

use App\Models\Branding;
use App\Models\HomepageSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;

class SecurityRuntimeService
{
    private const DEFAULTS = [
        'security_ssl_https_redirect' => 'disable',
        'security_admin_login_captcha' => 'disable',
        'security_reseller_login_captcha' => 'disable',
        'security_pin_expire_days' => 100,
        'security_password_expire_days' => 100,
        'security_password_strong' => 'yes',
        'security_request_interval_minutes' => 1,
        'security_session_timeout_minutes' => 20000,
        'security_support_ticket' => 'enable',
        'security_popup_notice' => 'on',
        'security_gp' => 'off',
        'security_robi' => 'off',
        'security_banglalink' => 'off',
        'security_airtel' => 'off',
        'security_teletalk' => 'off',
        'security_skitto' => 'off',
    ];

    private const OPERATOR_FIELDS = [
        'gp' => 'security_gp',
        'robi' => 'security_robi',
        'banglalink' => 'security_banglalink',
        'airtel' => 'security_airtel',
        'teletalk' => 'security_teletalk',
        'skitto' => 'security_skitto',
    ];

    private const CAPTCHA_SETTINGS = [
        'admin' => 'security_admin_login_captcha',
        'reseller' => 'security_reseller_login_captcha',
    ];

    public function isHttpsRedirectEnabled(): bool
    {
        return $this->setting('security_ssl_https_redirect') === 'enable';
    }

    public function isAdminLoginCaptchaEnabled(): bool
    {
        return $this->isLoginCaptchaEnabledFor('admin');
    }

    public function isResellerLoginCaptchaEnabled(): bool
    {
        return $this->isLoginCaptchaEnabledFor('reseller');
    }

    public function loginCaptchaQuestion(Request $request, string $context): ?string
    {
        if (! $this->isLoginCaptchaEnabledFor($context)) {
            return null;
        }

        $challenge = $request->session()->get($this->captchaSessionKey($context));

        if (! is_array($challenge) || blank($challenge['question'] ?? null) || blank($challenge['answer'] ?? null)) {
            $challenge = $this->newCaptchaChallenge();
            $request->session()->put($this->captchaSessionKey($context), $challenge);
        }

        return (string) $challenge['question'];
    }

    public function validateLoginCaptcha(Request $request, string $context, ?string $answer): bool
    {
        if (! $this->isLoginCaptchaEnabledFor($context)) {
            return true;
        }

        $challenge = $request->session()->pull($this->captchaSessionKey($context));

        if (! is_array($challenge) || blank($challenge['answer'] ?? null)) {
            return false;
        }

        return trim((string) $answer) === (string) $challenge['answer'];
    }

    public function isPopupNoticeEnabled(): bool
    {
        return $this->setting('security_popup_notice') === 'on';
    }

    public function isSupportTicketEnabled(): bool
    {
        return $this->setting('security_support_ticket') === 'enable';
    }

    public function supportTicketDisabledMessage(): string
    {
        return 'Support ticket is currently disabled.';
    }

    public function passwordRules(): array
    {
        if ($this->setting('security_password_strong') !== 'yes') {
            return ['required', 'string', 'min:6', 'confirmed'];
        }

        return ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()];
    }

    public function sessionTimeoutMinutes(): int
    {
        return max(0, (int) $this->setting('security_session_timeout_minutes'));
    }

    public function touchSessionActivity(Request $request): void
    {
        $request->session()->put($this->sessionActivityKey(), now()->timestamp);
    }

    public function sessionLastActivity(Request $request): ?int
    {
        $lastActivity = $request->session()->get($this->sessionActivityKey());

        return is_numeric($lastActivity) ? (int) $lastActivity : null;
    }

    public function passwordExpireDays(): int
    {
        return max(0, (int) $this->setting('security_password_expire_days'));
    }

    public function pinExpireDays(): int
    {
        return max(0, (int) $this->setting('security_pin_expire_days'));
    }

    public function isPasswordExpired(User $user): bool
    {
        return $this->credentialExpiredAt($user->password_changed_at ?? $user->created_at, $this->passwordExpireDays());
    }

    public function isPinExpired(User $user): bool
    {
        return $this->credentialExpiredAt($user->pin_changed_at ?? $user->created_at, $this->pinExpireDays());
    }

    public function expiredCredentialMessage(User $user): ?string
    {
        $passwordExpired = $this->isPasswordExpired($user);
        $pinExpired = $this->isPinExpired($user);

        if ($passwordExpired && $pinExpired) {
            return 'Your password and PIN have expired. Please update them to continue.';
        }

        if ($passwordExpired) {
            return 'Your password has expired. Please update it to continue.';
        }

        if ($pinExpired) {
            return 'Your PIN has expired. Please update it to continue.';
        }

        return null;
    }

    public function hasRecentRequest(string $table, int $userId): bool
    {
        $minutes = max(0, (int) $this->setting('security_request_interval_minutes'));

        if ($minutes < 1 || ! Schema::hasTable($table)) {
            return false;
        }

        return DB::table($table)
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }

    public function requestIntervalMessage(): string
    {
        $minutes = max(1, (int) $this->setting('security_request_interval_minutes'));

        return 'Please wait ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' before submitting another request.';
    }

    public function isOperatorAllowed(?string $operator): bool
    {
        $normalized = $this->normalizeOperator($operator);

        if ($normalized === null) {
            return true;
        }

        $configuredValue = $this->configuredSetting(self::OPERATOR_FIELDS[$normalized]);

        if (! in_array($configuredValue, ['on', 'off'], true)) {
            return true;
        }

        return $configuredValue === 'on';
    }

    public function operatorBlockedMessage(): string
    {
        return 'This operator is currently unavailable.';
    }

    public function driveBalanceType(): string
    {
        return $this->usesDriveBalance() ? 'drive_bal' : 'main_bal';
    }

    public function manualPaymentBalanceType(): string
    {
        return $this->usesBankBalance() ? 'bank_bal' : 'main_bal';
    }

    public function usesDriveBalance(): bool
    {
        $configuredValue = $this->configuredSetting('security_drive_balance');

        if (in_array($configuredValue, ['on', 'off'], true)) {
            return $configuredValue === 'on';
        }

        if (! Schema::hasTable('brandings') || ! Schema::hasColumn('brandings', 'drive_balance')) {
            return true;
        }

        return (Branding::query()->first()?->drive_balance ?? 'on') !== 'off';
    }

    public function usesBankBalance(): bool
    {
        $configuredValue = $this->configuredSetting('security_bank_balance');

        if (in_array($configuredValue, ['on', 'off'], true)) {
            return $configuredValue === 'on';
        }

        return true;
    }

    private function isLoginCaptchaEnabledFor(string $context): bool
    {
        $field = self::CAPTCHA_SETTINGS[$context] ?? null;

        return $field !== null && $this->setting($field) === 'enable';
    }

    private function captchaSessionKey(string $context): string
    {
        return 'security.login_captcha.' . $context;
    }

    private function sessionActivityKey(): string
    {
        return 'security.last_activity_at';
    }

    private function newCaptchaChallenge(): array
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);

        return [
            'question' => 'What is ' . $left . ' + ' . $right . '?',
            'answer' => (string) ($left + $right),
        ];
    }

    private function credentialExpiredAt($changedAt, int $days): bool
    {
        if ($days < 1 || blank($changedAt)) {
            return false;
        }

        return now()->subDays($days)->gte($changedAt);
    }

    private function normalizeOperator(?string $operator): ?string
    {
        $normalized = strtolower(preg_replace('/[^a-z]/i', '', (string) $operator));

        return match ($normalized) {
            'grameenphone', 'gp' => 'gp',
            'robi', 'rb' => 'robi',
            'banglalink', 'bl' => 'banglalink',
            'airtel', 'at' => 'airtel',
            'teletalk', 'tt' => 'teletalk',
            'skitto' => 'skitto',
            default => null,
        };
    }

    private function configuredSetting(string $key)
    {
        if (! Schema::hasTable('homepage_settings') || ! Schema::hasColumn('homepage_settings', $key)) {
            return null;
        }

        return HomepageSetting::query()->first()?->{$key};
    }

    private function setting(string $key)
    {
        if (! Schema::hasTable('homepage_settings') || ! Schema::hasColumn('homepage_settings', $key)) {
            return self::DEFAULTS[$key] ?? null;
        }

        return HomepageSetting::query()->first()?->{$key} ?? (self::DEFAULTS[$key] ?? null);
    }
}
