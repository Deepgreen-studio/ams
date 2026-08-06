<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsWidgetType;
use App\Models\User;
use Database\Factories\AnalyticsWidgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AnalyticsWidget extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'analytics_dashboard_id',
        'name',
        'key',
        'type',
        'category',
        'data_source',
        'query_config',
        'visualization_config',
        'position_x',
        'position_y',
        'width',
        'height',
        'sort_order',
        'refresh_interval_seconds',
        'is_visible',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyticsWidget $widget): void {
            if (blank($widget->uuid)) {
                $widget->uuid = (string) Str::uuid();
            }

            if (blank($widget->key) && filled($widget->name)) {
                $widget->key = Str::slug($widget->name, '_');
            }
        });
    }

    protected static function newFactory(): AnalyticsWidgetFactory
    {
        return AnalyticsWidgetFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AnalyticsWidgetType::class,
            'category' => AnalyticsCategory::class,
            'query_config' => 'array',
            'visualization_config' => 'array',
            'is_visible' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'key',
                'type',
                'category',
                'data_source',
                'is_visible',
                'sort_order',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(AnalyticsDashboard::class, 'analytics_dashboard_id');
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
