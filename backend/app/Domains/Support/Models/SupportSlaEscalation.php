<?php

namespace App\Domains\Support\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Support\Enums\SupportSlaEscalationLevel;
use App\Domains\Support\Enums\SupportSlaEscalationStatus;
use App\Domains\Support\Enums\SupportSlaEscalationTrigger;
use App\Domains\Support\Enums\SupportSlaMetric;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupportSlaEscalation extends Model
{
    protected $table = 'support_sla_escalations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'support_ticket_id',
        'support_sla_policy_id',
        'support_sla_escalation_rule_id',
        'company_id',
        'level',
        'trigger',
        'metric',
        'status',
        'triggered_at',
        'acknowledged_at',
        'resolved_at',
        'acknowledged_by',
        'assigned_to',
        'notes',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportSlaEscalation $escalation): void {
            if (blank($escalation->uuid)) {
                $escalation->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => SupportSlaEscalationLevel::class,
            'trigger' => SupportSlaEscalationTrigger::class,
            'metric' => SupportSlaMetric::class,
            'status' => SupportSlaEscalationStatus::class,
            'triggered_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(SupportSlaPolicy::class, 'support_sla_policy_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SupportSlaEscalationRule::class, 'support_sla_escalation_rule_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
