<?php

namespace App\Domains\Scheduler\Models;

use App\Domains\Scheduler\Enums\ScheduledJobRunStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ScheduledJobRun extends Model
{
    protected $table = 'scheduled_job_runs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'scheduled_job_id',
        'status',
        'trigger',
        'attempt',
        'queue_name',
        'queue_job_id',
        'payload',
        'result',
        'error_message',
        'started_at',
        'finished_at',
        'duration_ms',
        'triggered_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ScheduledJobRun $run): void {
            if (blank($run->uuid)) {
                $run->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ScheduledJobRunStatus::class,
            'payload' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(ScheduledJob::class, 'scheduled_job_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ScheduledJobLog::class)->latest('id');
    }

    public function triggerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
