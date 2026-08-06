<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Companies\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BusinessAnalyticsSnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'snapshot_date',
        'customers_total',
        'customers_new',
        'customers_active',
        'subscriptions_total',
        'subscriptions_active',
        'subscriptions_new',
        'mrr',
        'revenue_period',
        'application_sessions',
        'application_active_users',
        'feature_usage_count',
        'support_tickets_open',
        'support_tickets_new',
        'avg_health_score',
        'at_risk_customers',
        'metrics',
        'computed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (BusinessAnalyticsSnapshot $snapshot): void {
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
            'mrr' => 'decimal:2',
            'revenue_period' => 'decimal:2',
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
