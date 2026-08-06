<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Compliance\Enums\PolicyApprovalStatus;
use App\Models\User;
use Database\Factories\PolicyApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PolicyApproval extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'policy_id',
        'policy_version_id',
        'status',
        'requested_by',
        'reviewed_by',
        'comments',
        'requested_at',
        'decided_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (PolicyApproval $approval): void {
            if (blank($approval->uuid)) {
                $approval->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): PolicyApprovalFactory
    {
        return PolicyApprovalFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PolicyApprovalStatus::class,
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PolicyDocument::class, 'policy_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
