<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\WebhookDirection;
use App\Domains\Integrations\Enums\WebhookLogStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WebhookLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'webhook_id',
        'company_id',
        'webhook_event_id',
        'direction',
        'event_name',
        'status',
        'method',
        'url',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
        'attempts',
        'max_attempts',
        'next_retry_at',
        'error_message',
        'is_test',
        'triggered_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (WebhookLog $log): void {
            if (blank($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => WebhookDirection::class,
            'status' => WebhookLogStatus::class,
            'request_headers' => 'array',
            'response_headers' => 'array',
            'duration_ms' => 'integer',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'response_status' => 'integer',
            'next_retry_at' => 'datetime',
            'is_test' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'webhook_event_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
