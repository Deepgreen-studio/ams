<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationVersionStatus;
use App\Models\User;
use Database\Factories\ApplicationVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationVersion extends Model
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
        'version_number',
        'major',
        'minor',
        'patch',
        'build_number',
        'status',
        'release_date',
        'minimum_supported_version',
        'release_notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationVersion $version): void {
            if (blank($version->uuid)) {
                $version->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ApplicationVersionFactory
    {
        return ApplicationVersionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'major' => 'integer',
            'minor' => 'integer',
            'patch' => 'integer',
            'status' => ApplicationVersionStatus::class,
            'release_date' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'version_number',
                'major',
                'minor',
                'patch',
                'build_number',
                'status',
                'release_date',
                'minimum_supported_version',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(ApplicationRelease::class, 'application_version_id');
    }

    /**
     * @return array{major: int, minor: int, patch: int}
     */
    public function semverParts(): array
    {
        return [
            'major' => (int) $this->major,
            'minor' => (int) $this->minor,
            'patch' => (int) $this->patch,
        ];
    }
}
