<?php

namespace App\Domains\Support\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KnowledgeArticleVersion extends Model
{
    protected $table = 'knowledge_article_versions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'knowledge_article_id',
        'version',
        'title',
        'body',
        'body_format',
        'summary',
        'status',
        'snapshot',
        'reason',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeArticleVersion $version): void {
            if (blank($version->uuid)) {
                $version->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
