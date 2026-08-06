<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationDeliveryStatus;
use App\Domains\Notifications\Enums\NotificationEventKey;
use Database\Factories\NotificationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class NotificationLog extends Model
{
    /** @use HasFactory<NotificationLogFactory> */
    use HasFactory;

    protected $table = 'notification_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'notification_id',
        'company_id',
        'laravel_notification_id',
        'notifiable_type',
        'notifiable_id',
        'event_key',
        'channel',
        'status',
        'recipient',
        'subject',
        'body_preview',
        'error_message',
        'payload',
        'queued_at',
        'sent_at',
        'failed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (NotificationLog $log): void {
            if (blank($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): NotificationLogFactory
    {
        return NotificationLogFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_key' => NotificationEventKey::class,
            'channel' => NotificationChannelEnum::class,
            'status' => NotificationDeliveryStatus::class,
            'payload' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
