<?php

namespace App\Models;

use App\Domains\Authentication\Notifications\EmailVerificationNotification;
use App\Domains\Authentication\Notifications\PasswordResetNotification;
use App\Domains\Customers\Models\Customer;
use App\Domains\Notifications\Models\DatabaseNotification;
use App\Domains\Users\Enums\UserGender;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\UserLoginHistory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'status',
        'password',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'created_by',
        'updated_by',
        'email_verified_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'status' => UserStatus::class,
            'gender' => UserGender::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name',
                'last_name',
                'full_name',
                'email',
                'phone',
                'status',
                'is_active',
                'avatar',
                'timezone',
                'language',
                'gender',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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
        return '/storage/'.ltrim((string) $this->avatar, '/');
    }

    public function isAccountActive(): bool
    {
        $status = $this->status instanceof UserStatus
            ? $this->status
            : UserStatus::tryFrom((string) $this->status);

        return ($status?->isLoginAllowed() ?? false) && (bool) $this->is_active;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
            $this->full_name = trim($firstName.' '.$lastName);
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

        if ($this->status === null) {
            $this->status = UserStatus::Active;
            $this->is_active = true;
        }

        if (blank($this->timezone)) {
            $this->timezone = 'UTC';
        }

        if (blank($this->language)) {
            $this->language = 'en';
        }
    }
}
