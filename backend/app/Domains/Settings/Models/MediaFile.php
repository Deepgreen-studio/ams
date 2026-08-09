<?php

namespace App\Domains\Settings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MediaFile extends Model
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'folder_id',
        'filename',
        'original_name',
        'extension',
        'mime_type',
        'size',
        'disk',
        'path',
        'url',
        'meta',
        'uploaded_by',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'public_url',
        'human_size',
    ];

    protected static function booted(): void
    {
        static::creating(function (MediaFile $media): void {
            if (blank($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'meta' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['folder_id', 'original_name', 'disk', 'path', 'size'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (blank($this->path)) {
            return filled($this->url) ? $this->url : null;
        }

        try {
            $url = Storage::disk($this->disk)->url($this->path);
        } catch (\Throwable) {
            return filled($this->url) ? $this->url : null;
        }

        // Public disk: return a same-origin path so Vite/dev proxies and any host work.
        // Absolute APP_URL hosts (e.g. ams.test) often differ from the API proxy target.
        if ($this->disk === 'public') {
            $path = parse_url($url, PHP_URL_PATH);

            return is_string($path) && $path !== '' ? $path : $url;
        }

        return $url;
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
