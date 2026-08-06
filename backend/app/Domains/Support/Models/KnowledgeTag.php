<?php

namespace App\Domains\Support\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class KnowledgeTag extends Model
{
    protected $table = 'knowledge_tags';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeTag $tag): void {
            if (blank($tag->uuid)) {
                $tag->uuid = (string) Str::uuid();
            }
            if (blank($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeArticle::class,
            'knowledge_article_tag',
            'knowledge_tag_id',
            'knowledge_article_id'
        )->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
