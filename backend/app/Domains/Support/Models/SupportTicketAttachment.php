<?php

namespace App\Domains\Support\Models;

use App\Domains\Support\Enums\SupportTicketAttachmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportTicketAttachment extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'ticket_attachments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'support_ticket_id',
        'ticket_message_id',
        'attachment_type',
        'disk',
        'path',
        'original_filename',
        'extension',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportTicketAttachment $attachment): void {
            if (blank($attachment->uuid)) {
                $attachment->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachment_type' => SupportTicketAttachmentType::class,
            'size' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['attachment_type', 'original_filename', 'mime_type', 'size'])
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

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportTicketMessage::class, 'ticket_message_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/')
            || $this->attachment_type === SupportTicketAttachmentType::Screenshot;
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mime_type, 'video/')
            || $this->attachment_type === SupportTicketAttachmentType::Video;
    }

    public function isPreviewable(): bool
    {
        return $this->isImage()
            || $this->isVideo()
            || in_array(strtolower((string) $this->extension), ['pdf', 'txt'], true)
            || $this->mime_type === 'application/pdf';
    }
}
