<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\IntegrationAuthenticationType;
use App\Domains\Integrations\Enums\IntegrationHealthStatus;
use App\Domains\Integrations\Enums\IntegrationStatus;
use App\Domains\Integrations\Enums\IntegrationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Integration extends Model
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
        'name',
        'slug',
        'description',
        'type',
        'status',
        'authentication_type',
        'base_url',
        'api_version',
        'timeout',
        'retry_attempts',
        'default_headers',
        'default_query',
        'rate_limit_per_minute',
        'health_check_path',
        'credentials',
        'health_status',
        'last_health_check',
        'created_by',
        'updated_by',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'credentials',
    ];

    protected static function booted(): void
    {
        static::creating(function (Integration $integration): void {
            if (blank($integration->uuid)) {
                $integration->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IntegrationType::class,
            'status' => IntegrationStatus::class,
            'authentication_type' => IntegrationAuthenticationType::class,
            'health_status' => IntegrationHealthStatus::class,
            'timeout' => 'integer',
            'retry_attempts' => 'integer',
            'rate_limit_per_minute' => 'integer',
            'default_headers' => 'array',
            'default_query' => 'array',
            'credentials' => 'encrypted:array',
            'last_health_check' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'type',
                'status',
                'authentication_type',
                'base_url',
                'api_version',
                'timeout',
                'retry_attempts',
                'rate_limit_per_minute',
                'health_check_path',
                'health_status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getHasCredentialsAttribute(): bool
    {
        return ! empty($this->credentials);
    }

    /**
     * @return list<string>
     */
    public function getCredentialKeysAttribute(): array
    {
        if (! is_array($this->credentials)) {
            return [];
        }

        return array_values(array_keys(array_filter(
            $this->credentials,
            fn ($value) => $value !== null && $value !== ''
        )));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function connectionLogs(): HasMany
    {
        return $this->hasMany(IntegrationConnectionLog::class);
    }

    public function dataMappings(): HasMany
    {
        return $this->hasMany(DataMapping::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
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
