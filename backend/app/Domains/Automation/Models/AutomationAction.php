<?php

namespace App\Domains\Automation\Models;

use App\Domains\Automation\Enums\AutomationActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AutomationAction extends Model
{
    protected $table = 'automation_actions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'automation_rule_id',
        'action_type',
        'config',
        'is_enabled',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationAction $action): void {
            if (blank($action->uuid)) {
                $action->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_type' => AutomationActionType::class,
            'config' => 'array',
            'is_enabled' => 'boolean',
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
