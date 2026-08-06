<?php

namespace App\Domains\Workflows\Models;

use App\Domains\Workflows\Enums\WorkflowLogAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkflowLog extends Model
{
    protected $table = 'workflow_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'workflow_instance_id',
        'workflow_step_id',
        'action',
        'actor_id',
        'from_status',
        'to_status',
        'comment',
        'payload',
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkflowLog $log): void {
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
            'action' => WorkflowLogAction::class,
            'payload' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
