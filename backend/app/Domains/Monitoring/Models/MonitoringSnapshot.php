<?php

namespace App\Domains\Monitoring\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MonitoringSnapshot extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'integration_id', 'scope',
        'health_score', 'performance_score', 'uptime_percent', 'downtime_percent',
        'error_rate', 'avg_response_ms', 'webhook_success_rate', 'queue_health_score',
        'availability_status', 'authentication_status', 'rate_limit_status', 'server_status',
        'metrics', 'period_start', 'period_end',
    ];

    protected static function booted(): void
    {
        static::creating(function (MonitoringSnapshot $snapshot): void {
            if (blank($snapshot->uuid)) {
                $snapshot->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'health_score' => 'integer',
            'performance_score' => 'integer',
            'uptime_percent' => 'float',
            'downtime_percent' => 'float',
            'error_rate' => 'float',
            'avg_response_ms' => 'integer',
            'webhook_success_rate' => 'float',
            'queue_health_score' => 'integer',
            'metrics' => 'array',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
        ];
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
}
