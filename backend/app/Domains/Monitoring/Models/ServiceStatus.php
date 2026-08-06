<?php

namespace App\Domains\Monitoring\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Monitoring\Enums\HealthCheckStatus;
use App\Domains\Monitoring\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ServiceStatus extends Model
{
    protected $table = 'service_status';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'service_key',
        'service_type',
        'name',
        'status',
        'last_check_at',
        'last_success_at',
        'last_failure_at',
        'consecutive_failures',
        'uptime_percent',
        'avg_response_ms',
        'error_rate',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceStatus $status): void {
            if (blank($status->uuid)) {
                $status->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'status' => HealthCheckStatus::class,
            'last_check_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'uptime_percent' => 'float',
            'error_rate' => 'float',
            'metadata' => 'array',
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
}
