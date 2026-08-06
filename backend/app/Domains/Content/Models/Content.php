<?php

namespace App\Domains\Content\Models;

use App\Models\User;
use Database\Factories\ContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Content extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'content_type_id',
        'content_status_id',
        'content_category_id',
        'title',
        'slug',
        'summary',
        'excerpt',
        'body',
        'body_format',
        'editor_json',
        'featured_image',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_type',
        'schema_json',
        'metadata',
        'is_featured',
        'view_count',
        'last_viewed_at',
        'sort_order',
        'version',
        'current_workflow_level',
        'last_workflow_comment',
        'published_at',
        'published_by',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'created_by',
        'updated_by',
        'last_autosaved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Content $content): void {
            if (blank($content->uuid)) {
                $content->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ContentFactory
    {
        return ContentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'editor_json' => 'array',
            'schema_json' => 'array',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'version' => 'integer',
            'view_count' => 'integer',
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'last_autosaved_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'body_format' => \App\Domains\Content\Enums\ContentBodyFormat::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'content_type_id',
                'content_status_id',
                'content_category_id',
                'title',
                'slug',
                'excerpt',
                'is_featured',
                'published_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ContentStatus::class, 'content_status_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ContentCategory::class, 'content_category')
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContentTag::class, 'content_tag')
            ->withTimestamps();
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContentVersion::class)->orderByDesc('version');
    }

    public function workflowHistories(): HasMany
    {
        return $this->hasMany(ContentWorkflowHistory::class)->orderByDesc('created_at');
    }
}
