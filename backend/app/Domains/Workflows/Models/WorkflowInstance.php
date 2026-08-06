<?php

namespace App\Domains\Workflows\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Workflows\Enums\WorkflowInstanceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WorkflowInstance extends Model
{
    protected $table = 'workflow_instances';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'workflow_id',
        'company_id',
        'subject_type',
        'subject_id',
        'subject_label',
        'status',
        'current_step_id',
        'active_step_keys',
        'pending_approvers',
        'context',
        'metadata',
        'started_at',
        'due_at',
        'completed_at',
        'started_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkflowInstance $instance): void {
            if (blank($instance->uuid)) {
                $instance->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkflowInstanceStatus::class,
            'active_step_keys' => 'array',
            'pending_approvers' => 'array',
            'context' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowLog::class)->latest('id');
    }
}
