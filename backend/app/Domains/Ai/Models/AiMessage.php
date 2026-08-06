<?php

namespace App\Domains\Ai\Models;

use App\Domains\Ai\Enums\AiMessageRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiMessage extends Model
{
    protected $table = 'ai_messages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'ai_conversation_id', 'role', 'content', 'token_input', 'token_output',
        'model', 'finish_reason', 'tool_calls', 'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiMessage $message): void {
            if (blank($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => AiMessageRole::class,
            'tool_calls' => 'array',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
