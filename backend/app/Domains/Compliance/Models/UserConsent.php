<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\ConsentSource;
use App\Domains\Compliance\Enums\ConsentStatus;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Database\Factories\UserConsentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserConsent extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'consent_type_id',
        'user_id',
        'customer_id',
        'subject_email',
        'subject_name',
        'consent_version',
        'status',
        'granted',
        'consented_at',
        'withdrawn_at',
        'ip_address',
        'device',
        'user_agent',
        'source',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserConsent $consent): void {
            if (blank($consent->uuid)) {
                $consent->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): UserConsentFactory
    {
        return UserConsentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ConsentStatus::class,
            'source' => ConsentSource::class,
            'granted' => 'boolean',
            'consented_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'consent_type_id',
                'status',
                'granted',
                'consent_version',
                'source',
                'consented_at',
                'withdrawn_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ConsentHistory::class)->orderByDesc('created_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
