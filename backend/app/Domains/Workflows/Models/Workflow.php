<?php

namespace App\Domains\Workflows\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Workflows\Enums\WorkflowDefinitionStatus;
use App\Domains\Workflows\Enums\WorkflowType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Workflow extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'workflows';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'description',
        'type',
        'status',
        'version',
        'is_enabled',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Workflow $workflow): void {
            if (blank($workflow->uuid)) {
                $workflow->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WorkflowType::class,
            'status' => WorkflowDefinitionStatus::class,
            'is_enabled' => 'boolean',
            'metadata' => 'array',
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

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('sort_order');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
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
