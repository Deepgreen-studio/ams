<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationCategory;
use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVisibility;
use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use App\Models\User;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Application extends Model
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
        'integration_id',
        'name',
        'slug',
        'description',
        'platform',
        'category',
        'icon',
        'banner',
        'current_version',
        'minimum_supported_version',
        'status',
        'visibility',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Application $application): void {
            if (blank($application->uuid)) {
                $application->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ApplicationFactory
    {
        return ApplicationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => ApplicationPlatform::class,
            'category' => ApplicationCategory::class,
            'status' => ApplicationStatus::class,
            'visibility' => ApplicationVisibility::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'platform',
                'category',
                'status',
                'visibility',
                'current_version',
                'minimum_supported_version',
                'integration_id',
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

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ApplicationVersion::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(ApplicationEnvironment::class);
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(ApplicationConfiguration::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(ApplicationRelease::class);
    }

    public function crashReports(): HasMany
    {
        return $this->hasMany(ApplicationCrashReport::class);
    }

    public function healthMetrics(): HasMany
    {
        return $this->hasMany(ApplicationHealthMetric::class);
    }

    public function monitoringAlerts(): HasMany
    {
        return $this->hasMany(ApplicationMonitoringAlert::class);
    }

    public function analyticsDaily(): HasMany
    {
        return $this->hasMany(ApplicationAnalyticsDaily::class);
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
