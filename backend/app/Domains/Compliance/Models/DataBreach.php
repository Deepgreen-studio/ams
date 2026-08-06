<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\DataBreachSeverity;
use App\Domains\Compliance\Enums\DataBreachStatus;
use App\Domains\Compliance\Enums\DataBreachType;
use App\Models\User;
use Database\Factories\DataBreachFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DataBreach extends Model
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
        'breach_number',
        'title',
        'description',
        'breach_type',
        'status',
        'severity',
        'discovered_at',
        'occurred_at',
        'affected_user_count',
        'affected_users',
        'affected_data_categories',
        'personal_data_involved',
        'special_category_data',
        'risk_likelihood',
        'risk_impact',
        'risk_score',
        'risk_level',
        'risk_assessment_notes',
        'risk_assessed_at',
        'risk_assessed_by',
        'impact_analysis',
        'containment_summary',
        'contained_at',
        'recovery_summary',
        'recovered_at',
        'root_cause',
        'root_cause_at',
        'lessons_learned',
        'lessons_learned_at',
        'regulator_notification_required',
        'regulator_deadline_at',
        'regulator_notified_at',
        'regulator_reference',
        'customer_notification_required',
        'customer_notified_at',
        'assigned_to',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (DataBreach $breach): void {
            if (blank($breach->uuid)) {
                $breach->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): DataBreachFactory
    {
        return DataBreachFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'breach_type' => DataBreachType::class,
            'status' => DataBreachStatus::class,
            'severity' => DataBreachSeverity::class,
            'risk_level' => DataBreachSeverity::class,
            'discovered_at' => 'datetime',
            'occurred_at' => 'datetime',
            'affected_users' => 'array',
            'affected_data_categories' => 'array',
            'personal_data_involved' => 'boolean',
            'special_category_data' => 'boolean',
            'risk_likelihood' => 'integer',
            'risk_impact' => 'integer',
            'risk_score' => 'integer',
            'risk_assessed_at' => 'datetime',
            'contained_at' => 'datetime',
            'recovered_at' => 'datetime',
            'root_cause_at' => 'datetime',
            'lessons_learned_at' => 'datetime',
            'regulator_notification_required' => 'boolean',
            'regulator_deadline_at' => 'datetime',
            'regulator_notified_at' => 'datetime',
            'customer_notification_required' => 'boolean',
            'customer_notified_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'breach_number',
                'title',
                'breach_type',
                'status',
                'severity',
                'assigned_to',
                'risk_score',
                'risk_level',
                'affected_user_count',
                'regulator_notified_at',
                'customer_notified_at',
                'closed_at',
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function riskAssessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'risk_assessed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(BreachAction::class)->orderByDesc('created_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(BreachNotification::class)->orderByDesc('created_at');
    }
}
