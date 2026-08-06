<?php

namespace App\Domains\Notifications\Models;

use App\Models\User;
use Database\Factories\NotificationChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NotificationChannel extends Model
{
    /** @use HasFactory<NotificationChannelFactory> */
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'notification_channels';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'key',
        'name',
        'description',
        'is_enabled',
        'is_implemented',
        'is_system',
        'sort_order',
        'config',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (NotificationChannel $channel): void {
            if (blank($channel->uuid)) {
                $channel->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): NotificationChannelFactory
    {
        return NotificationChannelFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_implemented' => 'boolean',
            'is_system' => 'boolean',
            'config' => 'array',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
