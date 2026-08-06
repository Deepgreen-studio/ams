<?php

namespace App\Domains\Applications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationAnalyticsDaily extends Model
{
    /** @var list<string> */
    protected $table = 'application_analytics_daily';

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'application_id',
        'metric_date',
        'active_users',
        'daily_users',
        'monthly_users',
        'avg_session_seconds',
        'retention_d1',
        'retention_d7',
        'retention_d30',
        'installs',
        'uninstalls',
        'sessions',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationAnalyticsDaily $row): void {
            if (blank($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'active_users' => 'integer',
            'daily_users' => 'integer',
            'monthly_users' => 'integer',
            'avg_session_seconds' => 'integer',
            'retention_d1' => 'float',
            'retention_d7' => 'float',
            'retention_d30' => 'float',
            'installs' => 'integer',
            'uninstalls' => 'integer',
            'sessions' => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
