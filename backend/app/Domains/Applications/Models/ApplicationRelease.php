<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationReleaseApprovalStatus;
use App\Domains\Applications\Enums\ApplicationReleaseRollbackStatus;
use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationReleaseType;
use App\Models\User;
use Database\Factories\ApplicationReleaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationRelease extends Model
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
        'application_version_id',
        'environment_id',
        'name',
        'version_label',
        'release_type',
        'status',
        'approval_status',
        'rollback_status',
        'scheduled_at',
        'deployment_date',
        'deployed_at',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rolled_back_by',
        'rolled_back_at',
        'rollback_of_release_id',
        'plan_summary',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationRelease $release): void {
            if (blank($release->uuid)) {
                $release->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ApplicationReleaseFactory
    {
        return ApplicationReleaseFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'release_type' => ApplicationReleaseType::class,
            'status' => ApplicationReleaseStatus::class,
            'approval_status' => ApplicationReleaseApprovalStatus::class,
            'rollback_status' => ApplicationReleaseRollbackStatus::class,
            'scheduled_at' => 'datetime',
            'deployment_date' => 'datetime',
            'deployed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rolled_back_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'version_label',
                'release_type',
                'status',
                'approval_status',
                'rollback_status',
                'scheduled_at',
                'deployment_date',
                'approved_by',
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

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApplicationVersion::class, 'application_version_id');
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApplicationEnvironment::class, 'environment_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationReleaseNote::class, 'release_id')->orderBy('sort_order');
    }

    public function rollbackOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rollback_of_release_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rolledBackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
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
