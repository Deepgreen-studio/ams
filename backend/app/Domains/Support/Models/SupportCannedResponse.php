<?php

namespace App\Domains\Support\Models;

use App\Domains\Support\Enums\CannedResponseVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportCannedResponse extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'support_canned_responses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'title',
        'shortcut',
        'body',
        'body_format',
        'visibility',
        'user_id',
        'is_active',
        'usage_count',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportCannedResponse $response): void {
            if (blank($response->uuid)) {
                $response->uuid = (string) Str::uuid();
            }
            if (blank($response->body_format)) {
                $response->body_format = 'html';
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => CannedResponseVisibility::class,
            'is_active' => 'boolean',
            'usage_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'shortcut', 'visibility', 'is_active', 'sort_order', 'body'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $inner) use ($user): void {
            $inner->where(function (Builder $personal) use ($user): void {
                $personal->where('visibility', CannedResponseVisibility::Personal->value)
                    ->where('user_id', $user->id);
            })->orWhere('visibility', CannedResponseVisibility::Shared->value);
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function isShared(): bool
    {
        return $this->visibility === CannedResponseVisibility::Shared;
    }

    public function isPersonal(): bool
    {
        return $this->visibility === CannedResponseVisibility::Personal;
    }
}
