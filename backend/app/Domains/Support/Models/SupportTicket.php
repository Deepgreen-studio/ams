<?php

namespace App\Domains\Support\Models;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Models\Team;
use App\Domains\Customers\Models\Customer;
use App\Domains\Support\Enums\SupportTicketAssignmentType;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Enums\SupportSlaEscalationLevel;
use App\Domains\Support\Enums\SupportSlaStatus;
use App\Models\User;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportTicket extends Model
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
        'customer_id',
        'application_id',
        'department_id',
        'team_id',
        'ticket_number',
        'subject',
        'description',
        'priority',
        'category',
        'status',
        'assigned_to',
        'assignment_type',
        'assigned_at',
        'source',
        'involves_personal_data',
        'compliance_routed_at',
        'privacy_request_id',
        'created_by',
        'updated_by',
        'closed_at',
        'support_sla_policy_id',
        'sla_status',
        'escalation_level',
        'first_response_due_at',
        'resolution_due_at',
        'first_response_at',
        'resolved_at',
        'response_breached_at',
        'resolution_breached_at',
        'sla_paused_at',
        'sla_paused_seconds',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket): void {
            if (blank($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): SupportTicketFactory
    {
        return SupportTicketFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => SupportTicketPriority::class,
            'category' => SupportTicketCategory::class,
            'status' => SupportTicketStatus::class,
            'source' => SupportTicketSource::class,
            'assignment_type' => SupportTicketAssignmentType::class,
            'sla_status' => SupportSlaStatus::class,
            'escalation_level' => SupportSlaEscalationLevel::class,
            'involves_personal_data' => 'boolean',
            'compliance_routed_at' => 'datetime',
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'response_breached_at' => 'datetime',
            'resolution_breached_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'sla_paused_seconds' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ticket_number',
                'subject',
                'priority',
                'category',
                'status',
                'assigned_to',
                'assignment_type',
                'department_id',
                'team_id',
                'source',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function privacyRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Compliance\Models\PrivacyRequest::class, 'privacy_request_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
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

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SupportTicketStatusHistory::class)->orderByDesc('created_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'support_ticket_id')->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'support_ticket_id')->orderByDesc('created_at');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SupportSlaPolicy::class, 'support_sla_policy_id');
    }

    public function slaEscalations(): HasMany
    {
        return $this->hasMany(SupportSlaEscalation::class, 'support_ticket_id')->orderByDesc('triggered_at');
    }
}
