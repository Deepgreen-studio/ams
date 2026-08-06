<?php

namespace App\Domains\Applications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationHealthMetric extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'application_id',
        'application_version_id',
        'environment_id',
        'version_label',
        'recorded_at',
        'health_score',
        'crash_rate',
        'anr_rate',
        'api_error_rate',
        'avg_response_time_ms',
        'avg_memory_usage_mb',
        'avg_battery_usage',
        'crash_count',
        'anr_count',
        'api_error_count',
        'sample_size',
        'metadata',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationHealthMetric $metric): void {
            if (blank($metric->uuid)) {
                $metric->uuid = (string) Str::uuid();
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'health_score' => 'integer',
            'crash_rate' => 'float',
            'anr_rate' => 'float',
            'api_error_rate' => 'float',
            'avg_response_time_ms' => 'integer',
            'avg_memory_usage_mb' => 'float',
            'avg_battery_usage' => 'float',
            'crash_count' => 'integer',
            'anr_count' => 'integer',
            'api_error_count' => 'integer',
            'sample_size' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApplicationVersion::class, 'application_version_id');
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApplicationEnvironment::class, 'environment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
