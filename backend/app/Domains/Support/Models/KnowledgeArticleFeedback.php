<?php

namespace App\Domains\Support\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KnowledgeArticleFeedback extends Model
{
    protected $table = 'knowledge_article_feedback';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'knowledge_article_id',
        'user_id',
        'is_helpful',
        'comment',
        'ip_address',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeArticleFeedback $feedback): void {
            if (blank($feedback->uuid)) {
                $feedback->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_helpful' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'knowledge_article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
