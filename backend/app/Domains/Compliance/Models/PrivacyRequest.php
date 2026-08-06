<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\PrivacyIdentityVerificationStatus;
use App\Domains\Compliance\Enums\PrivacyRequestDecision;
use App\Domains\Compliance\Enums\PrivacyRequestStatus;
use App\Domains\Compliance\Enums\PrivacyRequestType;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Database\Factories\PrivacyRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PrivacyRequest extends Model
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
        'request_number',
        'request_type',
        'requester_name',
        'requester_email',
        'requester_phone',
        'customer_id',
        'support_ticket_id',
        'description',
        'identity_verification_status',
        'identity_verified_at',
        'identity_verified_by',
        'identity_verification_notes',
        'status',
        'assigned_to',
        'due_date',
        'completed_at',
        'decision',
        'decision_notes',
        'decision_at',
        'decision_by',
        'export_payload',
        'export_file_path',
        'export_generated_at',
        'deletion_confirmed_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrivacyRequest $request): void {
            if (blank($request->uuid)) {
                $request->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): PrivacyRequestFactory
    {
        return PrivacyRequestFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_type' => PrivacyRequestType::class,
            'status' => PrivacyRequestStatus::class,
            'identity_verification_status' => PrivacyIdentityVerificationStatus::class,
            'decision' => PrivacyRequestDecision::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'identity_verified_at' => 'datetime',
            'decision_at' => 'datetime',
            'export_generated_at' => 'datetime',
            'deletion_confirmed_at' => 'datetime',
            'export_payload' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'request_number',
                'request_type',
                'requester_name',
                'requester_email',
                'status',
                'identity_verification_status',
                'assigned_to',
                'decision',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Support\Models\SupportTicket::class, 'support_ticket_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function identityVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identity_verified_by');
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PrivacyRequestLog::class)->orderByDesc('created_at');
    }
}
