<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationConfigurationStatus;
use App\Domains\Applications\Enums\ApplicationConfigurationType;
use App\Models\User;
use Database\Factories\ApplicationConfigurationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationConfiguration extends Model
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
        'environment_id',
        'type',
        'name',
        'description',
        'payload',
        'status',
        'version',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationConfiguration $configuration): void {
            if (blank($configuration->uuid)) {
                $configuration->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ApplicationConfigurationFactory
    {
        return ApplicationConfigurationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ApplicationConfigurationType::class,
            'status' => ApplicationConfigurationStatus::class,
            'payload' => 'encrypted:array',
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'type',
                'status',
                'version',
                'is_active',
                'environment_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApplicationEnvironment::class, 'environment_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ApplicationConfigurationHistory::class, 'configuration_id');
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
