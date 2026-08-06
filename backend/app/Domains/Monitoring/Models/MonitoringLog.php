<?php

namespace App\Domains\Monitoring\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Monitoring\Enums\MonitoringLogCategory;
use App\Domains\Monitoring\Enums\MonitoringLogLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class MonitoringLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'level',
        'category',
        'source',
        'title',
        'message',
        'context',
        'related_type',
        'related_id',
        'occurred_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (MonitoringLog $log): void {
            if (blank($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
            if (blank($log->occurred_at)) {
                $log->occurred_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => MonitoringLogLevel::class,
            'category' => MonitoringLogCategory::class,
            'context' => 'array',
            'occurred_at' => 'datetime',
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

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
