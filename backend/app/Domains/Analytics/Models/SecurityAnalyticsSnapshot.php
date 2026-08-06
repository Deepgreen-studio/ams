<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Companies\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SecurityAnalyticsSnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'snapshot_date',
        'logins_success',
        'logins_failed',
        'permission_changes',
        'role_changes',
        'data_exports',
        'data_deletions',
        'gdpr_requests',
        'security_events',
        'api_key_uses',
        'api_errors',
        'risk_score',
        'metrics',
        'computed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (SecurityAnalyticsSnapshot $snapshot): void {
            if (blank($snapshot->uuid)) {
                $snapshot->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'metrics' => 'array',
            'computed_at' => 'datetime',
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
