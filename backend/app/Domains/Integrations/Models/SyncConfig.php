<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\SyncConflictStrategy;
use App\Domains\Integrations\Enums\SyncDirection;
use App\Domains\Integrations\Enums\SyncMode;
use App\Domains\Integrations\Enums\SyncTrigger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SyncConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id', 'integration_id', 'name', 'slug', 'description',
        'direction', 'default_mode', 'trigger_type', 'schedule_cron', 'is_enabled',
        'source_path', 'target_path', 'entity_type', 'conflict_strategy', 'batch_size',
        'cursor_field', 'cursor_value', 'field_mapping', 'filters', 'options',
        'record_snapshot', 'last_synced_at', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (SyncConfig $config): void {
            if (blank($config->uuid)) {
                $config->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'direction' => SyncDirection::class,
            'default_mode' => SyncMode::class,
            'trigger_type' => SyncTrigger::class,
            'conflict_strategy' => SyncConflictStrategy::class,
            'is_enabled' => 'boolean',
            'batch_size' => 'integer',
            'field_mapping' => 'array',
            'filters' => 'array',
            'options' => 'array',
            'record_snapshot' => 'array',
            'last_synced_at' => 'datetime',
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

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SyncRun::class);
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
