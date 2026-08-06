<?php

namespace App\Domains\Ai\Models;

use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AiConversation extends Model
{
    use SoftDeletes;

    protected $table = 'ai_conversations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'company_id', 'user_id', 'ai_provider_id', 'ai_prompt_id',
        'feature', 'context_type', 'context_id', 'title', 'status', 'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiConversation $conversation): void {
            if (blank($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
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
            'metadata' => 'array',
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

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(AiPrompt::class, 'ai_prompt_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class)->orderBy('id');
    }
}
