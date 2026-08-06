<?php

namespace App\Domains\Monitoring\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MonitoringAlertEvent extends Model
{
    protected $fillable = [
        'uuid', 'monitoring_alert_id', 'severity', 'status', 'metric_value',
        'message', 'context', 'acknowledged_at', 'acknowledged_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (MonitoringAlertEvent $event): void {
            if (blank($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'metric_value' => 'float',
            'context' => 'array',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(MonitoringAlert::class, 'monitoring_alert_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
