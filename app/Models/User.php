<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\ApiDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'mobile',
        'referral_code',
        'referred_by',
        'referral_coin',
        'nid',
        'profile_picture',
        'password',
        'password_changed_at',
        'pin',
        'pin_changed_at',
        'is_admin',
        'is_first_admin',
        'is_active',
        'permissions',
        'level',
        'parent_id',
        'main_bal',
        'bank_bal',
        'drive_bal',
        'stock',
        'fcm_token',
        'fcm_token_updated_at',
        'google_otp_secret',
        'google_otp_enabled',
        'google_otp_confirmed_at',
        'api_key',
        'api_access_enabled',
        'api_services',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin',
        'google_otp_secret',
        'api_key',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'pin' => 'hashed',
            'pin_changed_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_first_admin' => 'boolean',
            'is_active' => 'boolean',
            'fcm_token_updated_at' => 'datetime',
            'google_otp_enabled' => 'boolean',
            'google_otp_confirmed_at' => 'datetime',
            'api_access_enabled' => 'boolean',
            'api_services' => 'array',
            'referral_coin' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $referralCode = Str::upper(trim((string) ($user->referral_code ?? '')));

            if ($referralCode === '') {
                $referralCode = static::generateReferralCode();
            }

            $user->referral_code = $referralCode;
        });
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (self::query()->where('referral_code', $code)->exists());

        return $code;
    }

    public static function apiServiceOptions(): array
    {
        return [
            'recharge' => 'Flexiload Recharge',
            'drive' => 'Drive',
            'internet' => 'Internet Pack',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'rocket' => 'Rocket',
            'upay' => 'Upay',
        ];
    }

    public static function adminPermissionOptions(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'backup' => 'Backup',
            'recharge_history' => 'Recharge History',
            'payment_history' => 'Payment History',
            'manage_users' => 'Manage Users',
            'manage_resellers' => 'Manage Resellers',
            'manage_operators' => 'Manage Operators',
            'manage_offers' => 'Manage Offers',
            'payment_methods' => 'Payment Methods',
            'support_tickets' => 'Support Tickets',
            'settings' => 'Settings',
        ];
    }

    public static function resellerPermissionOptions(): array
    {
        return [
            'add_balance' => 'Add Balance',
            'drive' => 'Drive Offers',
            'internet' => 'Internet Packs',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'rocket' => 'Rocket',
            'upay' => 'Upay',
            'islami_bank' => 'Islami Bank',
            'pending_requests' => 'Pending Requests',
            'all_history' => 'All History',
            'drive_history' => 'Drive History',
            'profile' => 'Profile',
            'complaints' => 'Complaints',
        ];
    }

    public function permissionKeys(): array
    {
        $permissions = $this->permissions;

        if (blank($permissions)) {
            return [];
        }

        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $permissions = $decoded;
            } else {
                $permissions = array_map('trim', explode(',', $permissions));
            }
        }

        if ($permissions instanceof \Illuminate\Support\Collection) {
            $permissions = $permissions->all();
        }

        if (! is_array($permissions)) {
            return [];
        }

        return collect($permissions)
            ->filter(fn($permission) => filled($permission))
            ->map(fn($permission) => (string) $permission)
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_first_admin) {
            return true;
        }

        return in_array($permission, $this->permissionKeys(), true);
    }

    public function hasApprovedApiAccess(): bool
    {
        return $this->is_first_admin || (bool) $this->api_access_enabled;
    }

    public function enabledApiServices(): array
    {
        $services = $this->api_services;
        $allowedServices = array_keys(self::apiServiceOptions());

        if ($services === null || $services === '') {
            return $allowedServices;
        }

        if (is_string($services)) {
            $decoded = json_decode($services, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $services = $decoded;
            } else {
                $services = array_map('trim', explode(',', $services));
            }
        }

        if ($services instanceof \Illuminate\Support\Collection) {
            $services = $services->all();
        }

        if (! is_array($services)) {
            return $allowedServices;
        }

        return collect($services)
            ->filter(fn($service) => filled($service))
            ->map(fn($service) => trim((string) $service))
            ->filter(fn(string $service) => in_array($service, $allowedServices, true))
            ->unique()
            ->values()
            ->all();
    }

    public function hasEnabledApiService(string $service): bool
    {
        if ($this->is_first_admin) {
            return true;
        }

        return in_array($service, $this->enabledApiServices(), true);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function ensureReferralCode(): void
    {
        $referralCode = Str::upper(trim((string) ($this->referral_code ?? '')));
        $hasDuplicateCode = $referralCode !== ''
            && static::query()
            ->where('referral_code', $referralCode)
            ->where($this->getKeyName(), '!=', $this->getKey())
            ->exists();

        if ($referralCode !== '' && ! $hasDuplicateCode) {
            if ($this->referral_code !== $referralCode) {
                $this->forceFill([
                    'referral_code' => $referralCode,
                ])->save();
            }

            $this->referral_code = $referralCode;

            return;
        }

        $this->forceFill([
            'referral_code' => static::generateReferralCode(),
        ])->save();

        $this->refresh();
    }

    public function apiDomains()
    {
        return $this->hasMany(ApiDomain::class);
    }
    
    public function sentTransfers()
    {
        return $this->hasMany(BalanceTransfer::class, 'sender_id');
    }

    public function receivedTransfers()
    {
        return $this->hasMany(BalanceTransfer::class, 'receiver_id');
    }
}
