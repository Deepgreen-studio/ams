<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsReportStatus;
use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Enums\AnalyticsReportVisibility;
use App\Domains\Companies\Models\Company;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Models\User;
use Database\Factories\AnalyticsReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AnalyticsReport extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'category',
        'report_type',
        'status',
        'visibility',
        'is_saved',
        'is_scheduled',
        'query_config',
        'columns',
        'filters',
        'sorting',
        'grouping',
        'chart_config',
        'layout',
        'schedule_config',
        'scheduled_job_id',
        'format_defaults',
        'last_run_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyticsReport $report): void {
            if (blank($report->uuid)) {
                $report->uuid = (string) Str::uuid();
            }

            if (blank($report->slug) && filled($report->name)) {
                $report->slug = Str::slug($report->name);
            }
        });
    }

    protected static function newFactory(): AnalyticsReportFactory
    {
        return AnalyticsReportFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AnalyticsCategory::class,
            'report_type' => AnalyticsReportType::class,
            'status' => AnalyticsReportStatus::class,
            'visibility' => AnalyticsReportVisibility::class,
            'is_saved' => 'boolean',
            'is_scheduled' => 'boolean',
            'query_config' => 'array',
            'columns' => 'array',
            'filters' => 'array',
            'sorting' => 'array',
            'grouping' => 'array',
            'chart_config' => 'array',
            'layout' => 'array',
            'schedule_config' => 'array',
            'format_defaults' => 'array',
            'last_run_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'category',
                'report_type',
                'status',
                'visibility',
                'is_saved',
                'is_scheduled',
                'filters',
                'sorting',
                'grouping',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scheduledJob(): BelongsTo
    {
        return $this->belongsTo(ScheduledJob::class, 'scheduled_job_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AnalyticsReportRun::class)->latest();
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
