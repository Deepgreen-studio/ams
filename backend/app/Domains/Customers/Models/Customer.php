<?php

namespace App\Domains\Customers\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
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
        'customer_type',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'website',
        'industry',
        'country',
        'timezone',
        'language',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'display_name',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            if (blank($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'status' => CustomerStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'company_id',
                'customer_type',
                'first_name',
                'last_name',
                'company_name',
                'email',
                'phone',
                'website',
                'industry',
                'country',
                'timezone',
                'language',
                'status',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->customer_type instanceof CustomerType && $this->customer_type->requiresCompanyName()) {
                return (string) ($this->company_name ?: trim("{$this->first_name} {$this->last_name}") ?: $this->email);
            }

            $person = trim("{$this->first_name} {$this->last_name}");

            return $person !== '' ? $person : (string) ($this->company_name ?: $this->email);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CustomerApplication::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CustomerTask::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(CustomerCommunication::class);
    }

    public function portalUsers(): HasMany
    {
        return $this->hasMany(User::class, 'customer_id');
    }

    public function analyticsSnapshots(): HasMany
    {
        return $this->hasMany(CustomerAnalyticsSnapshot::class);
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
