<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\ConnectionRequestType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IntegrationConnectionLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'integration_id',
        'company_id',
        'request_type',
        'method',
        'url',
        'request_headers',
        'request_query',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
        'attempts',
        'success',
        'error_message',
        'triggered_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (IntegrationConnectionLog $log): void {
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
            'request_type' => ConnectionRequestType::class,
            'request_headers' => 'array',
            'request_query' => 'array',
            'response_headers' => 'array',
            'success' => 'boolean',
            'duration_ms' => 'integer',
            'attempts' => 'integer',
            'response_status' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
