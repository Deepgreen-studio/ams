<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\ComplianceCasePriority;
use App\Domains\Compliance\Enums\ComplianceCaseStatus;
use App\Domains\Compliance\Enums\ComplianceCaseType;
use App\Models\User;
use Database\Factories\ComplianceCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ComplianceCase extends Model
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
        'case_number',
        'title',
        'description',
        'case_type',
        'priority',
        'status',
        'assigned_to',
        'due_date',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ComplianceCase $case): void {
            if (blank($case->uuid)) {
                $case->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ComplianceCaseFactory
    {
        return ComplianceCaseFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'case_type' => ComplianceCaseType::class,
            'priority' => ComplianceCasePriority::class,
            'status' => ComplianceCaseStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'case_number',
                'title',
                'case_type',
                'priority',
                'status',
                'assigned_to',
                'due_date',
                'completed_at',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
