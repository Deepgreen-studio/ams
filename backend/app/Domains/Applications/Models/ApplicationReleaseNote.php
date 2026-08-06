<?php

namespace App\Domains\Applications\Models;

use App\Domains\Applications\Enums\ApplicationReleaseNoteAudience;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationReleaseNote extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'release_id',
        'locale',
        'title',
        'content',
        'audience',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationReleaseNote $note): void {
            if (blank($note->uuid)) {
                $note->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => ApplicationReleaseNoteAudience::class,
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(ApplicationRelease::class, 'release_id');
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
