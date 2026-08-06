<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationMonitoringAlertOperator;
use App\Domains\Applications\Enums\ApplicationMonitoringAlertSeverity;
use App\Domains\Applications\Enums\ApplicationMonitoringMetric;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApplicationMonitoringAlert extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'application_id',
        'name',
        'metric',
        'operator',
        'threshold',
        'severity',
        'is_active',
        'cooldown_minutes',
        'last_triggered_at',
        'message',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationMonitoringAlert $alert): void {
            if (blank($alert->uuid)) {
                $alert->uuid = (string) Str::uuid();
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metric' => ApplicationMonitoringMetric::class,
            'operator' => ApplicationMonitoringAlertOperator::class,
            'severity' => ApplicationMonitoringAlertSeverity::class,
            'threshold' => 'float',
            'is_active' => 'boolean',
            'cooldown_minutes' => 'integer',
            'last_triggered_at' => 'datetime',
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

    public function events(): HasMany
    {
        return $this->hasMany(ApplicationMonitoringAlertEvent::class, 'alert_id');
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
