<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Compliance\Enums\BreachNotificationChannel;
use App\Domains\Compliance\Enums\BreachNotificationStatus;
use App\Domains\Compliance\Enums\BreachNotificationType;
use App\Models\User;
use Database\Factories\BreachNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BreachNotification extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'data_breach_id',
        'notification_type',
        'channel',
        'recipient',
        'subject',
        'message',
        'status',
        'sent_at',
        'acknowledged_at',
        'sent_by',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (BreachNotification $notification): void {
            if (blank($notification->uuid)) {
                $notification->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): BreachNotificationFactory
    {
        return BreachNotificationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_type' => BreachNotificationType::class,
            'channel' => BreachNotificationChannel::class,
            'status' => BreachNotificationStatus::class,
            'sent_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function dataBreach(): BelongsTo
    {
        return $this->belongsTo(DataBreach::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
