<?php

namespace App\Domains\Content\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContentVersion extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'content_id',
        'version',
        'status',
        'snapshot',
        'reason',
        'created_by',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentVersion $version): void {
            if (blank($version->uuid)) {
                $version->uuid = (string) Str::uuid();
            }
            if (blank($version->created_at)) {
                $version->created_at = now();
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
            'created_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
