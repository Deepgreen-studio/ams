<?php

namespace App\Domains\Workflows\Models;

use App\Domains\Workflows\Enums\WorkflowStepType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkflowStep extends Model
{
    protected $table = 'workflow_steps';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'workflow_id',
        'name',
        'step_key',
        'step_type',
        'sort_order',
        'position_x',
        'position_y',
        'config',
        'next_step_keys',
        'on_approve_step_key',
        'on_reject_step_key',
        'is_required',
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkflowStep $step): void {
            if (blank($step->uuid)) {
                $step->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step_type' => WorkflowStepType::class,
            'config' => 'array',
            'next_step_keys' => 'array',
            'is_required' => 'boolean',
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

    /**
     * @return list<string>
     */
    public function approverRoleNames(): array
    {
        $roles = $this->config['approver_roles'] ?? [];

        return is_array($roles) ? array_values(array_map('strval', $roles)) : [];
    }

    /**
     * @return list<string>
     */
    public function approverUserUuids(): array
    {
        $users = $this->config['approver_user_uuids'] ?? [];

        return is_array($users) ? array_values(array_map('strval', $users)) : [];
    }

    public function timeoutMinutes(): ?int
    {
        $value = $this->config['timeout_minutes'] ?? null;

        return $value !== null ? (int) $value : null;
    }

    public function escalateToRole(): ?string
    {
        $role = $this->config['escalate_to_role'] ?? null;

        return blank($role) ? null : (string) $role;
    }
}
