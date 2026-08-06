<?php

namespace App\Domains\Support\Models;

use App\Domains\Support\Enums\SupportTicketWorkflowAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupportTicketStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'support_ticket_status_histories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'support_ticket_id',
        'from_status',
        'to_status',
        'action',
        'acted_by',
        'comments',
        'metadata',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportTicketStatusHistory $history): void {
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
            'action' => SupportTicketWorkflowAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
