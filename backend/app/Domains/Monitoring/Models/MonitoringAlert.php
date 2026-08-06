<?php

namespace App\Domains\Monitoring\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Monitoring\Enums\MonitoringMetric;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MonitoringAlert extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id', 'name', 'metric', 'operator', 'threshold',
        'is_enabled', 'cooldown_minutes', 'channels', 'description',
        'last_triggered_at', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (MonitoringAlert $alert): void {
            if (blank($alert->uuid)) {
                $alert->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'metric' => MonitoringMetric::class,
            'threshold' => 'float',
            'is_enabled' => 'boolean',
            'cooldown_minutes' => 'integer',
            'channels' => 'array',
            'last_triggered_at' => 'datetime',
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

    public function events(): HasMany
    {
        return $this->hasMany(MonitoringAlertEvent::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
