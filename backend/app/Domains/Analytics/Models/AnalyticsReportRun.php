<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\AnalyticsReportFormat;
use App\Domains\Analytics\Enums\AnalyticsReportRunStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnalyticsReportRun extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'analytics_report_id',
        'status',
        'format',
        'trigger',
        'filters_snapshot',
        'result_meta',
        'row_count',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'error_message',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyticsReportRun $run): void {
            if (blank($run->uuid)) {
                $run->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AnalyticsReportRunStatus::class,
            'format' => AnalyticsReportFormat::class,
            'filters_snapshot' => 'array',
            'result_meta' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(AnalyticsReport::class, 'analytics_report_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function downloadUrl(): ?string
    {
        if (blank($this->file_path)) {
            return null;
        }

        return Storage::disk($this->disk())->url($this->file_path);
    }

    public function disk(): string
    {
        return (string) config('filesystems.analytics_reports_disk', 'local');
    }
}
