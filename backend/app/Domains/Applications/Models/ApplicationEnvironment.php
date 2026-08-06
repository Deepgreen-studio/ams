<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationEnvironmentHealthStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentType;
use App\Models\User;
use Database\Factories\ApplicationEnvironmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationEnvironment extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'application_id',
        'name',
        'slug',
        'type',
        'api_url',
        'web_url',
        'status',
        'health_status',
        'last_health_check',
        'variables',
        'is_current',
        'created_by',
        'updated_by',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'variables',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationEnvironment $environment): void {
            if (blank($environment->uuid)) {
                $environment->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ApplicationEnvironmentFactory
    {
        return ApplicationEnvironmentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ApplicationEnvironmentType::class,
            'status' => ApplicationEnvironmentStatus::class,
            'health_status' => ApplicationEnvironmentHealthStatus::class,
            'last_health_check' => 'datetime',
            'variables' => 'encrypted:array',
            'is_current' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'type',
                'api_url',
                'web_url',
                'status',
                'health_status',
                'is_current',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getHasVariablesAttribute(): bool
    {
        return ! empty($this->variables);
    }

    /**
     * @return list<array{key: string, masked_value: string|null, has_value: bool, is_masked: bool}>
     */
    public function getMaskedVariablesAttribute(): array
    {
        if (! is_array($this->variables)) {
            return [];
        }

        $masked = [];
        foreach ($this->variables as $key => $value) {
            $hasValue = $value !== null && $value !== '';
            $masked[] = [
                'key' => (string) $key,
                'masked_value' => $hasValue ? '********' : null,
                'has_value' => $hasValue,
                'is_masked' => true,
            ];
        }

        return $masked;
    }

    /**
     * @return list<string>
     */
    public function getVariableKeysAttribute(): array
    {
        if (! is_array($this->variables)) {
            return [];
        }

        return array_values(array_map('strval', array_keys($this->variables)));
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
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
