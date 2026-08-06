<?php

namespace App\Domains\Queue\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Queue\Enums\QueueJobStatus;
use App\Domains\Queue\Enums\QueueJobType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QueueJobTrack extends Model
{
    protected $fillable = [
        'uuid', 'job_uuid', 'job_class', 'display_name', 'queue', 'priority', 'type',
        'status', 'attempts', 'max_tries', 'delay_seconds', 'payload', 'exception',
        'queued_at', 'available_at', 'started_at', 'finished_at', 'failed_at',
        'company_id', 'triggered_by', 'related_type', 'related_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (QueueJobTrack $track): void {
            if (blank($track->uuid)) {
                $track->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => QueueJobType::class,
            'status' => QueueJobStatus::class,
            'payload' => 'array',
            'attempts' => 'integer',
            'max_tries' => 'integer',
            'delay_seconds' => 'integer',
            'queued_at' => 'datetime',
            'available_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
