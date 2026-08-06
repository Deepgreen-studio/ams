<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationMonitoringAlertSeverity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationMonitoringAlertEvent extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'application_id',
        'alert_id',
        'metric',
        'threshold',
        'observed_value',
        'severity',
        'status',
        'message',
        'context',
        'triggered_at',
        'acknowledged_at',
        'acknowledged_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationMonitoringAlertEvent $event): void {
            if (blank($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'severity' => ApplicationMonitoringAlertSeverity::class,
            'threshold' => 'float',
            'observed_value' => 'float',
            'context' => 'array',
            'triggered_at' => 'datetime',
            'acknowledged_at' => 'datetime',
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

    public function alert(): BelongsTo
    {
        return $this->belongsTo(ApplicationMonitoringAlert::class, 'alert_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
