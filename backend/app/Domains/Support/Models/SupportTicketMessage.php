<?php

namespace App\Domains\Support\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Support\Enums\SupportTicketMessageAuthorType;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportTicketMessage extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'ticket_messages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'support_ticket_id',
        'company_id',
        'author_id',
        'author_type',
        'visibility',
        'body',
        'body_format',
        'is_system',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportTicketMessage $message): void {
            if (blank($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'author_type' => SupportTicketMessageAuthorType::class,
            'visibility' => SupportTicketMessageVisibility::class,
            'is_system' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['visibility', 'body_format', 'author_type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'ticket_message_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(SupportTicketMessageRead::class, 'ticket_message_id');
    }
}
