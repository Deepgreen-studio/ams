<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Companies\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExecutiveAnalyticsSnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'snapshot_date',
        'mrr',
        'revenue_period',
        'customers_total',
        'customers_active',
        'customers_new',
        'applications_total',
        'subscriptions_active',
        'support_tickets_open',
        'support_sla_on_track',
        'support_sla_breached',
        'compliance_cases_open',
        'compliance_risk_score',
        'system_health_score',
        'system_uptime_percent',
        'security_risk_score',
        'business_score',
        'scorecards',
        'metrics',
        'computed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ExecutiveAnalyticsSnapshot $snapshot): void {
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
            'system_uptime_percent' => 'decimal:2',
            'scorecards' => 'array',
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
