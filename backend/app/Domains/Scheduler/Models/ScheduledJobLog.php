<?php

namespace App\Domains\Scheduler\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ScheduledJobLog extends Model
{
    protected $table = 'scheduled_job_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'scheduled_job_run_id',
        'level',
        'message',
        'context',
    ];

    protected static function booted(): void
    {
        static::creating(function (ScheduledJobLog $log): void {
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
            'context' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ScheduledJobRun::class, 'scheduled_job_run_id');
    }
}
