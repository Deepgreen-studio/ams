<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'user_id',
        'event_key',
        'email_enabled',
        'in_app_enabled',
        'sms_enabled',
        'push_enabled',
        'whatsapp_enabled',
        'slack_enabled',
        'teams_enabled',
        'webhook_enabled',
    ];

    protected static function booted(): void
    {
        static::creating(function (NotificationPreference $preference): void {
            if (blank($preference->uuid)) {
                $preference->uuid = (string) Str::uuid();
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
            'email_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'slack_enabled' => 'boolean',
            'teams_enabled' => 'boolean',
            'webhook_enabled' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
