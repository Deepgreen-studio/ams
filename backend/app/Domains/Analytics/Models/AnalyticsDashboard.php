<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Database\Factories\AnalyticsDashboardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AnalyticsDashboard extends Model
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
        'kind',
        'category',
        'status',
        'visibility',
        'layout',
        'filters',
        'settings',
        'is_default',
        'is_system',
        'is_shared',
        'is_template',
        'template_source_id',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyticsDashboard $dashboard): void {
            if (blank($dashboard->uuid)) {
                $dashboard->uuid = (string) Str::uuid();
            }

            if (blank($dashboard->slug) && filled($dashboard->name)) {
                $dashboard->slug = Str::slug($dashboard->name);
            }
        });
    }

    protected static function newFactory(): AnalyticsDashboardFactory
    {
        return AnalyticsDashboardFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AnalyticsDashboardKind::class,
            'category' => AnalyticsCategory::class,
            'status' => AnalyticsDashboardStatus::class,
            'visibility' => AnalyticsDashboardVisibility::class,
            'layout' => 'array',
            'filters' => 'array',
            'settings' => 'array',
            'is_default' => 'boolean',
            'is_system' => 'boolean',
            'is_shared' => 'boolean',
            'is_template' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'kind',
                'category',
                'status',
                'visibility',
                'is_default',
                'is_shared',
                'is_template',
                'filters',
                'settings',
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

    public function templateSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'template_source_id');
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(AnalyticsWidget::class)->orderBy('sort_order')->orderBy('position_y')->orderBy('position_x');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(AnalyticsDashboardShare::class);
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
