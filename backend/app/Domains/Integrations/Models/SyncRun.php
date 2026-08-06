<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\SyncDirection;
use App\Domains\Integrations\Enums\SyncMode;
use App\Domains\Integrations\Enums\SyncRunStatus;
use App\Domains\Integrations\Enums\SyncTrigger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SyncRun extends Model
{
    protected $fillable = [
        'uuid', 'sync_config_id', 'company_id', 'integration_id', 'trigger', 'mode',
        'direction', 'status', 'started_at', 'completed_at', 'failed_at',
        'total_records', 'imported', 'exported', 'updated', 'failed', 'skipped',
        'progress_percent', 'error_message', 'meta', 'triggered_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (SyncRun $run): void {
            if (blank($run->uuid)) {
                $run->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'trigger' => SyncTrigger::class,
            'mode' => SyncMode::class,
            'direction' => SyncDirection::class,
            'status' => SyncRunStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'total_records' => 'integer',
            'imported' => 'integer',
            'exported' => 'integer',
            'updated' => 'integer',
            'failed' => 'integer',
            'skipped' => 'integer',
            'progress_percent' => 'integer',
            'meta' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(SyncConfig::class, 'sync_config_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
