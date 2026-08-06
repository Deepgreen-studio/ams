<?php

namespace App\Domains\Scheduler\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Scheduler\Enums\ScheduledJobType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ScheduledJob extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'scheduled_jobs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'description',
        'job_type',
        'handler_key',
        'schedule_cron',
        'timezone',
        'run_at',
        'delay_minutes',
        'queue_name',
        'is_enabled',
        'without_overlapping',
        'max_attempts',
        'timeout_seconds',
        'payload',
        'metadata',
        'last_run_at',
        'next_run_at',
        'last_status',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ScheduledJob $job): void {
            if (blank($job->uuid)) {
                $job->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'job_type' => ScheduledJobType::class,
            'is_enabled' => 'boolean',
            'without_overlapping' => 'boolean',
            'payload' => 'array',
            'metadata' => 'array',
            'run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ScheduledJobRun::class)->latest('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
