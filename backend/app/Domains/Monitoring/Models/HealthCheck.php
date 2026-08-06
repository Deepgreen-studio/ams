<?php

namespace App\Domains\Monitoring\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Monitoring\Enums\HealthCheckStatus;
use App\Domains\Monitoring\Enums\HealthCheckType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HealthCheck extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'monitoring_snapshot_id',
        'check_type',
        'name',
        'status',
        'response_ms',
        'message',
        'metadata',
        'checked_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (HealthCheck $check): void {
            if (blank($check->uuid)) {
                $check->uuid = (string) Str::uuid();
            }
            if (blank($check->checked_at)) {
                $check->checked_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_type' => HealthCheckType::class,
            'status' => HealthCheckStatus::class,
            'metadata' => 'array',
            'checked_at' => 'datetime',
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

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(MonitoringSnapshot::class, 'monitoring_snapshot_id');
    }
}
