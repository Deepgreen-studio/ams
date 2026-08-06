<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\WebhookDirection;
use App\Domains\Integrations\Enums\WebhookSignatureAlgorithm;
use App\Domains\Integrations\Enums\WebhookStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Webhook extends Model
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'integration_id',
        'name',
        'slug',
        'description',
        'direction',
        'status',
        'url',
        'secret',
        'signature_algorithm',
        'signature_header',
        'subscribed_events',
        'headers',
        'timeout',
        'retry_attempts',
        'retry_delay_seconds',
        'verify_ssl',
        'last_triggered_at',
        'last_success_at',
        'last_failure_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'secret',
    ];

    protected static function booted(): void
    {
        static::creating(function (Webhook $webhook): void {
            if (blank($webhook->uuid)) {
                $webhook->uuid = (string) Str::uuid();
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
            'status' => WebhookStatus::class,
            'signature_algorithm' => WebhookSignatureAlgorithm::class,
            'subscribed_events' => 'array',
            'headers' => 'array',
            'secret' => 'encrypted',
            'timeout' => 'integer',
            'retry_attempts' => 'integer',
            'retry_delay_seconds' => 'integer',
            'verify_ssl' => 'boolean',
            'last_triggered_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'direction',
                'status',
                'url',
                'signature_algorithm',
                'timeout',
                'retry_attempts',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getHasSecretAttribute(): bool
    {
        return filled($this->secret);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toEngineConfig(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'direction' => $this->direction?->value ?? $this->direction,
            'status' => $this->status?->value ?? $this->status,
            'url' => $this->url,
            'secret' => $this->secret,
            'signature_algorithm' => $this->signature_algorithm?->value ?? $this->signature_algorithm,
            'signature_header' => $this->signature_header,
            'headers' => $this->headers ?? [],
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retry_attempts,
            'retry_delay_seconds' => $this->retry_delay_seconds,
            'verify_ssl' => $this->verify_ssl,
        ];
    }
}
