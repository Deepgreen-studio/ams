<?php

namespace App\Domains\Customers\Models;

use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Applications\Models\ApplicationVersion;
use App\Domains\Customers\Enums\CustomerApplicationOwnershipType;
use App\Domains\Customers\Enums\CustomerApplicationStatus;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Support\Models\SupportSlaPolicy;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Database\Factories\CustomerApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomerApplication extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'assignment_number',
        'customer_id',
        'application_id',
        'platform',
        'application_environment_id',
        'application_version_id',
        'build_label',
        'application_release_id',
        'integration_id',
        'support_sla_policy_id',
        'owner_contact_id',
        'ownership_type',
        'status',
        'activated_at',
        'expires_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerApplication $assignment): void {
            if (blank($assignment->uuid)) {
                $assignment->uuid = (string) Str::uuid();
            }
        });

        static::created(function (CustomerApplication $assignment): void {
            if (blank($assignment->assignment_number)) {
                $assignment->forceFill([
                    'assignment_number' => sprintf('ASN-%08d', $assignment->id),
                ])->saveQuietly();
            }
        });
    }

    protected static function newFactory(): CustomerApplicationFactory
    {
        return CustomerApplicationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ownership_type' => CustomerApplicationOwnershipType::class,
            'status' => CustomerApplicationStatus::class,
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApplicationEnvironment::class, 'application_environment_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApplicationVersion::class, 'application_version_id');
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(ApplicationRelease::class, 'application_release_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SupportSlaPolicy::class, 'support_sla_policy_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'customer_application_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'customer_application_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'customer_application_id');
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function ownerContact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'owner_contact_id');
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
