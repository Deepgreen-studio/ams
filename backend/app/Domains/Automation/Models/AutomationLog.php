<?php

namespace App\Domains\Automation\Models;

use App\Domains\Automation\Enums\AutomationLogStatus;
use App\Domains\Automation\Enums\AutomationTriggerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AutomationLog extends Model
{
    protected $table = 'automation_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'automation_rule_id',
        'status',
        'trigger_type',
        'event_key',
        'context',
        'actions_result',
        'message',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationLog $log): void {
            if (blank($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AutomationLogStatus::class,
            'trigger_type' => AutomationTriggerType::class,
            'context' => 'array',
            'actions_result' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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
