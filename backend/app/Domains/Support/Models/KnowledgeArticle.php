<?php

namespace App\Domains\Support\Models;

use App\Domains\Content\Models\Content;
use App\Domains\Support\Enums\KnowledgeArticleStatus;
use App\Domains\Support\Enums\KnowledgeArticleType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KnowledgeArticle extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'knowledge_articles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'knowledge_category_id',
        'content_id',
        'type',
        'status',
        'title',
        'slug',
        'summary',
        'body',
        'body_format',
        'video_url',
        'featured_image',
        'sync_from_cms',
        'is_featured',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'version',
        'sort_order',
        'published_at',
        'published_by',
        'author_id',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeArticle $article): void {
            if (blank($article->uuid)) {
                $article->uuid = (string) Str::uuid();
            }
            if (blank($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => KnowledgeArticleType::class,
            'status' => KnowledgeArticleStatus::class,
            'sync_from_cms' => 'boolean',
            'is_featured' => 'boolean',
            'view_count' => 'integer',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
            'version' => 'integer',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'type', 'status', 'content_id', 'knowledge_category_id', 'is_featured'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'knowledge_category_id');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeTag::class,
            'knowledge_article_tag',
            'knowledge_article_id',
            'knowledge_tag_id'
        )->withTimestamps();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeArticleVersion::class, 'knowledge_article_id')->orderByDesc('version');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(KnowledgeArticleFeedback::class, 'knowledge_article_id');
    }

    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'knowledge_article_relations',
            'knowledge_article_id',
            'related_article_id'
        )->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
