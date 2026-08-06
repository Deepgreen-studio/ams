<?php

namespace App\Domains\Automation\Models;

use App\Domains\Automation\Enums\AutomationTriggerType;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AutomationRule extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'automation_rules';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'description',
        'trigger_type',
        'event_key',
        'schedule_cron',
        'schedule_timezone',
        'delay_minutes',
        'condition_logic',
        'is_enabled',
        'priority',
        'last_triggered_at',
        'next_run_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationRule $rule): void {
            if (blank($rule->uuid)) {
                $rule->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger_type' => AutomationTriggerType::class,
            'is_enabled' => 'boolean',
            'metadata' => 'array',
            'last_triggered_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(AutomationCondition::class)->orderBy('sort_order');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class)->orderBy('sort_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class)->latest('id');
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
