<?php

namespace App\Domains\Support\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportSlaPolicy extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'support_sla_policies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'support_sla_calendar_id',
        'name',
        'code',
        'priority',
        'category',
        'response_target_minutes',
        'resolution_target_minutes',
        'at_risk_percent',
        'business_hours_only',
        'is_default',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportSlaPolicy $policy): void {
            if (blank($policy->uuid)) {
                $policy->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => SupportTicketPriority::class,
            'category' => SupportTicketCategory::class,
            'response_target_minutes' => 'integer',
            'resolution_target_minutes' => 'integer',
            'at_risk_percent' => 'integer',
            'business_hours_only' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'code',
                'priority',
                'category',
                'response_target_minutes',
                'resolution_target_minutes',
                'at_risk_percent',
                'business_hours_only',
                'is_default',
                'is_active',
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

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(SupportSlaCalendar::class, 'support_sla_calendar_id');
    }

    public function escalationRules(): HasMany
    {
        return $this->hasMany(SupportSlaEscalationRule::class, 'support_sla_policy_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'support_sla_policy_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
