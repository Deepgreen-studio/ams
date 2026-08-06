<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Notifications\Enums\NotificationTemplateApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NotificationTemplateApproval extends Model
{
    protected $table = 'notification_template_approvals';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'notification_template_id',
        'notification_template_version_id',
        'status',
        'requested_by',
        'reviewed_by',
        'comments',
        'requested_at',
        'decided_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (NotificationTemplateApproval $approval): void {
            if (blank($approval->uuid)) {
                $approval->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => NotificationTemplateApprovalStatus::class,
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplateVersion::class, 'notification_template_version_id');
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
