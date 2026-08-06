<?php

namespace App\Domains\Support\Models;

use App\Domains\Support\Enums\SupportSlaEscalationLevel;
use App\Domains\Support\Enums\SupportSlaEscalationTrigger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportSlaEscalationRule extends Model
{
    protected $table = 'support_sla_escalation_rules';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'support_sla_policy_id',
        'level',
        'trigger',
        'sort_order',
        'notify_role',
        'notify_user_id',
        'reassign_to_manager',
        'is_active',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportSlaEscalationRule $rule): void {
            if (blank($rule->uuid)) {
                $rule->uuid = (string) Str::uuid();
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
            'sort_order' => 'integer',
            'reassign_to_manager' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(SupportSlaPolicy::class, 'support_sla_policy_id');
    }

    public function notifyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notify_user_id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(SupportSlaEscalation::class, 'support_sla_escalation_rule_id');
    }
}
