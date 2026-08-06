<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\RiskCategory;
use App\Domains\Compliance\Enums\RiskLevel;
use App\Domains\Compliance\Enums\RiskRegisterStatus;
use App\Models\User;
use Database\Factories\RiskRegisterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RiskRegister extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'risk_register';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'dpia_assessment_id',
        'risk_number',
        'title',
        'description',
        'category',
        'status',
        'likelihood',
        'impact',
        'risk_score',
        'risk_level',
        'residual_likelihood',
        'residual_impact',
        'residual_score',
        'residual_level',
        'mitigation_plan',
        'review_due_at',
        'identified_at',
        'closed_at',
        'owner_id',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (RiskRegister $risk): void {
            if (blank($risk->uuid)) {
                $risk->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): RiskRegisterFactory
    {
        return RiskRegisterFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RiskCategory::class,
            'status' => RiskRegisterStatus::class,
            'risk_level' => RiskLevel::class,
            'residual_level' => RiskLevel::class,
            'likelihood' => 'integer',
            'impact' => 'integer',
            'risk_score' => 'integer',
            'residual_likelihood' => 'integer',
            'residual_impact' => 'integer',
            'residual_score' => 'integer',
            'review_due_at' => 'date',
            'identified_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'risk_number',
                'title',
                'category',
                'status',
                'likelihood',
                'impact',
                'risk_score',
                'risk_level',
                'owner_id',
                'review_due_at',
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

    public function dpiaAssessment(): BelongsTo
    {
        return $this->belongsTo(DpiaAssessment::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
        return $this->hasMany(RiskAction::class)->orderByDesc('created_at');
    }
}
