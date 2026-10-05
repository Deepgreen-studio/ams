<?php

namespace App\Models;

use App\Domains\Authentication\Notifications\EmailVerificationNotification;
use App\Domains\Authentication\Notifications\PasswordResetNotification;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Models\CompanyLocation;
use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Models\Team;
use App\Domains\Customers\Models\Customer;
use App\Domains\Notifications\Models\DatabaseNotification;
use App\Domains\Users\Enums\InvitationStatus;
use App\Domains\Users\Enums\UserGender;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Settings\Support\ApplicationTimezone;
use App\Domains\Users\Models\UserLoginHistory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword;

    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'first_name',
        'last_name',
        'full_name',
        'name',
        'email',
        'phone',
        'avatar',
        'gender',
        'date_of_birth',
        'timezone',
        'language',
        'department_id',
        'team_id',
        'location_id',
        'status',
        'invitation_status',
        'invitation_sent_at',
        'invitation_expires_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'password',
        'is_active',
        'is_protected',
        'last_login_at',
        'last_login_ip',
        'created_by',
        'updated_by',
        'deleted_by',
        'email_verified_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }

            $user->syncIdentityFields();
        });

        static::saving(function (User $user): void {
            $user->syncIdentityFields();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_protected' => 'boolean',
            'status' => UserStatus::class,
            'invitation_status' => InvitationStatus::class,
            'gender' => UserGender::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('users')
            ->logOnly([
                'first_name',
                'last_name',
                'full_name',
                'email',
                'phone',
                'status',
                'invitation_status',
                'is_active',
                'avatar',
                'timezone',
                'language',
                'gender',
                'created_by',
                'updated_by',
                'deleted_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'User created',
            'updated' => 'User updated',
            'deleted' => 'User deleted',
            'restored' => 'User restored',
            default => $eventName,
        };
    }

    public function tapActivity($activity, string $eventName): void
    {
        $request = request();

        $activity->properties = $activity->properties->merge([
            'ip' => $request?->ip(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        if (Str::startsWith($this->avatar, ['http://', 'https://'])) {
            return $this->avatar;
        }

        // Relative path so the SPA (Vite proxy / same origin) can load the file.
        // Absolute APP_URL hosts (e.g. ams.test) are often unreachable from the Vite dev server.
        return '/storage/' . ltrim((string) $this->avatar, '/');
    }

    public function isAccountActive(): bool
    {
        $status = $this->status instanceof UserStatus
            ? $this->status
            : UserStatus::tryFrom((string) $this->status);

        return ($status?->isLoginAllowed() ?? false) && (bool) $this->is_active;
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && filled($this->two_factor_secret);
    }

    public function requiresMfaEnrollment(): bool
    {
        return $this->hasRole('super-admin') && ! $this->hasConfirmedTwoFactor();
    }

    public function isProtectedAccount(): bool
    {
        return (bool) $this->is_protected;
    }

    public function lifecycleStatus(): string
    {
        $invitation = $this->invitation_status instanceof InvitationStatus
            ? $this->invitation_status
            : InvitationStatus::tryFrom((string) $this->invitation_status);

        if ($invitation === InvitationStatus::Pending) {
            return 'pending_invitation';
        }

        if ($invitation === InvitationStatus::Expired) {
            return 'expired';
        }

        $status = $this->status instanceof UserStatus
            ? $this->status
            : UserStatus::tryFrom((string) $this->status);

        return $status?->value ?? UserStatus::Inactive->value;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by')->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by')->withTrashed();
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by')->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['is_primary', 'status'])
            ->withTimestamps();
    }

    public function isPortalCustomer(): bool
    {
        return $this->customer_id !== null && $this->hasRole('customer');
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class);
    }

    /**
     * Laravel database-channel notifications (separate from enterprise `notifications`).
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    public function readNotifications(): MorphMany
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordResetNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new EmailVerificationNotification);
    }

    protected function syncIdentityFields(): void
    {
        $firstName = trim((string) ($this->first_name ?? ''));
        $lastName = trim((string) ($this->last_name ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            $this->full_name = trim($firstName . ' ' . $lastName);
        }

        if (filled($this->full_name)) {
            $this->name = $this->full_name;
        } elseif (filled($this->name) && blank($this->full_name)) {
            $this->full_name = $this->name;
        }

        if ($this->isDirty('status') && $this->status !== null) {
            $status = $this->status instanceof UserStatus
                ? $this->status
                : UserStatus::tryFrom((string) $this->status);

            $this->is_active = $status?->isLoginAllowed() ?? false;
        } elseif ($this->isDirty('is_active')) {
            $this->status = $this->is_active ? UserStatus::Active : UserStatus::Inactive;
        }

        if ($this->isProtectedAccount()) {
            $this->status = UserStatus::Active;
            $this->is_active = true;
            $this->invitation_status = InvitationStatus::Accepted;
        } elseif ($this->status === null) {
            $this->status = UserStatus::Active;
            $this->is_active = true;
        }

        if (blank($this->timezone)) {
            $this->timezone = ApplicationTimezone::name();
        }

        if (blank($this->language)) {
            $this->language = 'en';
        }
    }
}
