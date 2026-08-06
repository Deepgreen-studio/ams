<?php

namespace App\Domains\Customers\Models;

use App\Domains\Customers\Enums\CustomerRiskLevel;
use Database\Factories\CustomerAnalyticsSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerAnalyticsSnapshot extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'snapshot_date',
        'applications_total',
        'applications_active',
        'integrations_total',
        'api_usage_count',
        'login_activity_count',
        'support_tickets_open',
        'support_tickets_total',
        'subscription_status',
        'subscription_active',
        'health_score',
        'activity_score',
        'risk_level',
        'metrics',
        'computed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerAnalyticsSnapshot $snapshot): void {
            if (blank($snapshot->uuid)) {
                $snapshot->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): CustomerAnalyticsSnapshotFactory
    {
        return CustomerAnalyticsSnapshotFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'subscription_active' => 'boolean',
            'applications_total' => 'integer',
            'applications_active' => 'integer',
            'integrations_total' => 'integer',
            'api_usage_count' => 'integer',
            'login_activity_count' => 'integer',
            'support_tickets_open' => 'integer',
            'support_tickets_total' => 'integer',
            'health_score' => 'integer',
            'activity_score' => 'integer',
            'risk_level' => CustomerRiskLevel::class,
            'metrics' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
