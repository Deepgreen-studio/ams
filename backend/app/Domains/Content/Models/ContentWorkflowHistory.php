<?php

namespace App\Domains\Content\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContentWorkflowHistory extends Model
{
    public $timestamps = false;

    protected $table = 'content_workflow_histories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'content_id',
        'from_status',
        'to_status',
        'action',
        'approval_level',
        'acted_by',
        'comments',
        'metadata',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentWorkflowHistory $history): void {
            if (blank($history->uuid)) {
                $history->uuid = (string) Str::uuid();
            }
            if (blank($history->created_at)) {
                $history->created_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
