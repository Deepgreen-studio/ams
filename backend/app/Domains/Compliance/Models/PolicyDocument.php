<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\PolicyDocumentStatus;
use App\Domains\Compliance\Enums\PolicyType;
use App\Domains\Content\Models\Content;
use App\Models\User;
use Database\Factories\PolicyDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PolicyDocument extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'policies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'policy_number',
        'title',
        'slug',
        'policy_type',
        'description',
        'body',
        'status',
        'current_version',
        'content_id',
        'effective_at',
        'expires_at',
        'review_due_at',
        'published_at',
        'assigned_to',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (PolicyDocument $policy): void {
            if (blank($policy->uuid)) {
                $policy->uuid = (string) Str::uuid();
            }
            if (blank($policy->slug) && filled($policy->title)) {
                $policy->slug = Str::slug($policy->title);
            }
        });
    }

    protected static function newFactory(): PolicyDocumentFactory
    {
        return PolicyDocumentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'policy_type' => PolicyType::class,
            'status' => PolicyDocumentStatus::class,
            'current_version' => 'integer',
            'effective_at' => 'datetime',
            'expires_at' => 'datetime',
            'review_due_at' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'policy_number',
                'title',
                'policy_type',
                'status',
                'current_version',
                'content_id',
                'published_at',
                'assigned_to',
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

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
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

    public function versions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class, 'policy_id')->orderByDesc('version');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PolicyApproval::class, 'policy_id')->orderByDesc('created_at');
    }
}
