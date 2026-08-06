<?php

namespace App\Domains\Content\Models;

use App\Domains\Content\Enums\MediaType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MediaLibraryItem extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'media_library';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'media_group_uuid',
        'folder_id',
        'version',
        'is_current',
        'name',
        'original_name',
        'filename',
        'extension',
        'mime_type',
        'type',
        'size',
        'disk',
        'path',
        'url',
        'alt_text',
        'caption',
        'description',
        'metadata',
        'checksum',
        'uploaded_by',
        'created_by',
        'updated_by',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'human_size',
        'public_url',
        'is_image',
        'is_previewable',
    ];

    protected static function booted(): void
    {
        static::creating(function (MediaLibraryItem $item): void {
            if (blank($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
            if (blank($item->media_group_uuid)) {
                $item->media_group_uuid = (string) Str::uuid();
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
            'is_current' => 'boolean',
            'size' => 'integer',
            'metadata' => 'array',
            'type' => MediaType::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['folder_id', 'name', 'original_name', 'type', 'size', 'is_current', 'version'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (filled($this->url)) {
            return $this->url;
        }

        if (blank($this->path)) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }

    public function getIsImageAttribute(): bool
    {
        return ($this->type instanceof MediaType ? $this->type : MediaType::tryFrom((string) $this->type)) === MediaType::Image;
    }

    public function getIsPreviewableAttribute(): bool
    {
        $type = $this->type instanceof MediaType ? $this->type : MediaType::tryFrom((string) $this->type);

        return in_array($type, [MediaType::Image, MediaType::Video, MediaType::Document], true)
            && in_array(strtolower((string) $this->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'pdf'], true);
    }
}
