<?php

namespace App\Domains\Content\Models;

use App\Models\User;
use Database\Factories\ContentTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContentTag extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'seo_title',
        'seo_description',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentTag $tag): void {
            if (blank($tag->uuid)) {
                $tag->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): ContentTagFactory
    {
        return ContentTagFactory::new();
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'description',
                'seo_title',
                'seo_description',
                'is_active',
                'sort_order',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_tag')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
