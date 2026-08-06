<?php

namespace App\Domains\Ai\Models;

use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiUsageLog extends Model
{
    protected $table = 'ai_usage_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'company_id', 'user_id', 'ai_provider_id', 'ai_conversation_id', 'ai_message_id',
        'feature', 'operation', 'driver', 'model', 'tokens_in', 'tokens_out', 'latency_ms',
        'status', 'error_message', 'cost_estimate', 'request_id', 'meta',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiUsageLog $log): void {
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
            'feature' => AiFeature::class,
            'cost_estimate' => 'decimal:6',
            'meta' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
