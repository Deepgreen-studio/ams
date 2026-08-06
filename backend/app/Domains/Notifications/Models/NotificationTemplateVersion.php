<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NotificationTemplateVersion extends Model
{
    public $timestamps = false;

    protected $table = 'notification_template_versions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'notification_template_id',
        'version',
        'status',
        'name',
        'channel',
        'locale',
        'event_key',
        'subject',
        'body',
        'available_variables',
        'priority',
        'snapshot',
        'reason',
        'is_restore',
        'restored_from_version',
        'created_by',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (NotificationTemplateVersion $version): void {
            if (blank($version->uuid)) {
                $version->uuid = (string) Str::uuid();
            }
            if (blank($version->created_at)) {
                $version->created_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_key' => NotificationEventKey::class,
            'channel' => NotificationChannelEnum::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationTemplateStatus::class,
            'available_variables' => 'array',
            'snapshot' => 'array',
            'is_restore' => 'boolean',
            'created_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
