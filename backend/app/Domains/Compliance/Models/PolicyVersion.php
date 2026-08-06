<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Compliance\Enums\PolicyDocumentStatus;
use App\Models\User;
use Database\Factories\PolicyVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PolicyVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'policy_id',
        'version',
        'status',
        'title',
        'body',
        'snapshot',
        'reason',
        'is_restore',
        'restored_from_version',
        'created_by',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (PolicyVersion $version): void {
            if (blank($version->uuid)) {
                $version->uuid = (string) Str::uuid();
            }
            if (blank($version->created_at)) {
                $version->created_at = now();
            }
        });
    }

    protected static function newFactory(): PolicyVersionFactory
    {
        return PolicyVersionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PolicyDocumentStatus::class,
            'version' => 'integer',
            'snapshot' => 'array',
            'is_restore' => 'boolean',
            'restored_from_version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PolicyDocument::class, 'policy_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PolicyApproval::class, 'policy_version_id');
    }
}
