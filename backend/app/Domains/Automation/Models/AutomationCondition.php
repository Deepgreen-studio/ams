<?php

namespace App\Domains\Automation\Models;

use App\Domains\Automation\Enums\AutomationConditionOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AutomationCondition extends Model
{
    protected $table = 'automation_conditions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'automation_rule_id',
        'field',
        'operator',
        'value',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationCondition $condition): void {
            if (blank($condition->uuid)) {
                $condition->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operator' => AutomationConditionOperator::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
}
