<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Notifications\Enums\NotificationChannel;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Models\User;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'notifications';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'user_id',
        'channel',
        'template',
        'event_key',
        'title',
        'message',
        'status',
        'priority',
        'laravel_notification_id',
        'template_id',
        'data',
        'scheduled_at',
        'sent_at',
        'read_at',
        'clicked_at',
        'click_count',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Notification $notification): void {
            if (blank($notification->uuid)) {
                $notification->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationStatus::class,
            'priority' => NotificationPriority::class,
            'data' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'clicked_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'notification_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function markAsClicked(): void
    {
        $this->forceFill([
            'clicked_at' => $this->clicked_at ?? now(),
            'click_count' => ((int) $this->click_count) + 1,
        ])->save();
    }
}
