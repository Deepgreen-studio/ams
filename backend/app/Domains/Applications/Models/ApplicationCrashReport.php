<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationCrashSeverity;
use App\Domains\Applications\Enums\ApplicationCrashStatus;
use App\Domains\Applications\Enums\ApplicationCrashType;
use App\Models\User;
use Database\Factories\ApplicationCrashReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationCrashReport extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'application_id',
        'application_version_id',
        'version_label',
        'type',
        'severity',
        'status',
        'title',
        'message',
        'stack_trace',
        'crash_log',
        'fingerprint',
        'occurrence_count',
        'device_id',
        'device_model',
        'device_manufacturer',
        'device_os',
        'device_os_version',
        'device_meta',
        'endpoint',
        'http_status',
        'response_time_ms',
        'memory_usage_mb',
        'battery_level',
        'occurred_at',
        'resolved_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationCrashReport $report): void {
            if (blank($report->uuid)) {
                $report->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ApplicationCrashReportFactory
    {
        return ApplicationCrashReportFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ApplicationCrashType::class,
            'severity' => ApplicationCrashSeverity::class,
            'status' => ApplicationCrashStatus::class,
            'device_meta' => 'array',
            'metadata' => 'array',
            'occurrence_count' => 'integer',
            'http_status' => 'integer',
            'response_time_ms' => 'integer',
            'memory_usage_mb' => 'float',
            'battery_level' => 'float',
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'severity', 'status', 'title', 'version_label', 'occurrence_count'])
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
