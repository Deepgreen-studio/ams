<?php

namespace App\Domains\Integrations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SyncLog extends Model
{
    protected $fillable = [
        'uuid', 'sync_run_id', 'sync_config_id', 'level', 'action',
        'record_key', 'message', 'context',
    ];

    protected static function booted(): void
    {
        static::creating(function (SyncLog $log): void {
            if (blank($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

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
        return $this->belongsTo(SyncRun::class, 'sync_run_id');
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(SyncConfig::class, 'sync_config_id');
    }
}
