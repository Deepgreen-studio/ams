<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\DpiaStatus;
use App\Domains\Compliance\Enums\DpiaTemplate;
use App\Domains\Compliance\Enums\RiskLevel;
use App\Models\User;
use Database\Factories\DpiaAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DpiaAssessment extends Model
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
        'assessment_number',
        'title',
        'description',
        'template_code',
        'status',
        'wizard_step',
        'wizard_payload',
        'processing_purpose',
        'data_categories',
        'data_subjects',
        'processing_operations',
        'necessity_proportionality',
        'consultation_notes',
        'overall_risk_score',
        'overall_risk_level',
        'residual_risk_score',
        'residual_risk_level',
        'mitigation_summary',
        'review_due_at',
        'next_review_at',
        'reviewed_at',
        'reviewed_by',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'approval_notes',
        'rejected_at',
        'rejected_by',
        'rejection_notes',
        'assigned_to',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (DpiaAssessment $assessment): void {
            if (blank($assessment->uuid)) {
                $assessment->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): DpiaAssessmentFactory
    {
        return DpiaAssessmentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template_code' => DpiaTemplate::class,
            'status' => DpiaStatus::class,
            'overall_risk_level' => RiskLevel::class,
            'residual_risk_level' => RiskLevel::class,
            'wizard_step' => 'integer',
            'wizard_payload' => 'array',
            'data_categories' => 'array',
            'data_subjects' => 'array',
            'overall_risk_score' => 'integer',
            'residual_risk_score' => 'integer',
            'review_due_at' => 'date',
            'next_review_at' => 'date',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'assessment_number',
                'title',
                'template_code',
                'status',
                'overall_risk_score',
                'overall_risk_level',
                'assigned_to',
                'review_due_at',
                'approved_at',
                'rejected_at',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function risks(): HasMany
    {
        return $this->hasMany(RiskRegister::class)->orderByDesc('created_at');
    }
}
