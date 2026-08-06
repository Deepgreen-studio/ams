<?php

namespace App\Domains\Support\Services;

use App\Domains\Support\Enums\SupportTicketAttachmentType;
use App\Domains\Support\Enums\SupportTicketMessageAuthorType;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use App\Domains\Support\Enums\SupportTicketWorkflowAction;
use App\Domains\Support\Events\SupportTicketAttachmentUploaded;
use App\Domains\Support\Events\SupportTicketMessageCreated;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Models\SupportTicketAttachment;
use App\Domains\Support\Models\SupportTicketMessage;
use App\Domains\Support\Models\SupportTicketMessageRead;
use App\Domains\Support\Repositories\SupportTicketAttachmentRepository;
use App\Domains\Support\Repositories\SupportTicketMessageRepository;
use App\Domains\Support\Repositories\SupportTicketRepository;
use App\Domains\Support\Repositories\SupportTicketStatusHistoryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketConversationService
{
    /**
     * @var list<string>
     */
    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'ppt', 'pptx',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg',
        'mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v',
        'zip', 'rar', '7z',
    ];

    private const MAX_UPLOAD_KB = 102400; // 100 MB for videos

    public function __construct(
        private readonly SupportTicketRepository $ticketRepository,
        private readonly SupportTicketMessageRepository $messageRepository,
        private readonly SupportTicketAttachmentRepository $attachmentRepository,
        private readonly SupportTicketStatusHistoryRepository $historyRepository,
        private readonly SupportSlaTrackingService $slaTrackingService,
    ) {}

    /**
     * @param  list<string>|null  $visibilities
     * @return array{
     *   messages: Collection<int, SupportTicketMessage>,
     *   unread_count: int,
     *   attachment_count: int
     * }
     */
    public function conversation(string $ticketIdentifier, User $viewer, ?array $visibilities = null): array
    {
        $ticket = $this->ticketRepository->findByIdentifierOrFail($ticketIdentifier);
        $messages = $this->messageRepository->forTicket($ticket->id, $visibilities);

        return [
            'messages' => $messages,
            'unread_count' => $this->messageRepository->unreadCountForUser($ticket->id, $viewer->id),
            'attachment_count' => $ticket->attachments()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function createMessage(
        string $ticketIdentifier,
        array $data,
        User $actor,
        array $files = []
    ): SupportTicketMessage {
        return DB::transaction(function () use ($ticketIdentifier, $data, $actor, $files): SupportTicketMessage {
            $ticket = $this->ticketRepository->findByIdentifierOrFail($ticketIdentifier);
            $visibility = SupportTicketMessageVisibility::tryFrom((string) ($data['visibility'] ?? 'public'))
                ?? SupportTicketMessageVisibility::Public;

            $authorType = SupportTicketMessageAuthorType::tryFrom((string) ($data['author_type'] ?? ''))
                ?? SupportTicketMessageAuthorType::Agent;

            if ($authorType === SupportTicketMessageAuthorType::Customer) {
                $visibility = SupportTicketMessageVisibility::Public;
            }

            $body = trim(strip_tags((string) ($data['body'] ?? ''), '<p><br><strong><em><u><ul><ol><li><a><blockquote><code><pre><h1><h2><h3><h4><span>'));
            if ($body === '' || $body === '<p></p>') {
                throw new ApiException('Message body is required.', 422);
            }

            $message = $this->messageRepository->createMessage([
                'support_ticket_id' => $ticket->id,
                'company_id' => $ticket->company_id,
                'author_id' => $actor->id,
                'author_type' => $authorType->value,
                'visibility' => $visibility->value,
                'body' => $body,
                'body_format' => (string) ($data['body_format'] ?? 'html'),
                'is_system' => false,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($files as $file) {
                $this->storeAttachment($ticket, $message, $file, $actor, $data['attachment_type'] ?? null);
            }

            $message = $message->fresh([
                'author:id,uuid,full_name,email',
                'attachments',
                'reads',
            ]) ?? $message;

            SupportTicketMessageRead::query()->updateOrCreate(
                [
                    'ticket_message_id' => $message->id,
                    'user_id' => $actor->id,
                ],
                ['read_at' => now()]
            );

            $this->historyRepository->recordForTicket(
                $ticket,
                SupportTicketWorkflowAction::StatusChanged->value,
                $ticket->status?->value ?? (string) $ticket->status,
                $ticket->status?->value ?? (string) $ticket->status,
                $actor->id,
                $visibility->label().' posted',
                [
                    'conversation' => true,
                    'message_uuid' => $message->uuid,
                    'visibility' => $visibility->value,
                ]
            );

            event(new SupportTicketMessageCreated($message->loadMissing('ticket'), $actor));
            $this->slaTrackingService->recordFirstResponse($ticket->fresh() ?? $ticket, $actor, $visibility);

            return $message->load(['author:id,uuid,full_name,email', 'attachments', 'reads']);
        });
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, SupportTicketAttachment>
     */
    public function uploadAttachments(
        string $ticketIdentifier,
        array $files,
        User $actor,
        ?string $messageIdentifier = null,
        ?string $attachmentType = null
    ): Collection {
        return DB::transaction(function () use ($ticketIdentifier, $files, $actor, $messageIdentifier, $attachmentType): Collection {
            $ticket = $this->ticketRepository->findByIdentifierOrFail($ticketIdentifier);
            $message = null;

            if (! blank($messageIdentifier)) {
                $message = $this->messageRepository->findByIdentifierOrFail($messageIdentifier);
                if ((int) $message->support_ticket_id !== (int) $ticket->id) {
                    throw new ApiException('Attachment message does not belong to this ticket.', 422);
                }
            }

            $created = collect();
            foreach ($files as $file) {
                $created->push($this->storeAttachment($ticket, $message, $file, $actor, $attachmentType));
            }

            return $created->values();
        });
    }

    public function markRead(string $ticketIdentifier, User $viewer, ?array $messageIds = null): int
    {
        $ticket = $this->ticketRepository->findByIdentifierOrFail($ticketIdentifier);
        $query = SupportTicketMessage::query()->where('support_ticket_id', $ticket->id);

        if ($messageIds !== null && $messageIds !== []) {
            $query->whereIn('uuid', $messageIds);
        }

        $count = 0;
        foreach ($query->pluck('id') as $messageId) {
            SupportTicketMessageRead::query()->updateOrCreate(
                [
                    'ticket_message_id' => $messageId,
                    'user_id' => $viewer->id,
                ],
                ['read_at' => now()]
            );
            $count++;
        }

        return $count;
    }

    public function downloadAttachment(string $ticketIdentifier, string $attachmentIdentifier): StreamedResponse
    {
        $attachment = $this->resolveAttachment($ticketIdentifier, $attachmentIdentifier);
        $this->assertFileExists($attachment);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_filename
        );
    }

    public function previewAttachment(string $ticketIdentifier, string $attachmentIdentifier): StreamedResponse
    {
        $attachment = $this->resolveAttachment($ticketIdentifier, $attachmentIdentifier);
        $this->assertFileExists($attachment);

        if (! $attachment->isPreviewable()) {
            throw new ApiException('This attachment cannot be previewed inline.', 422);
        }

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_filename,
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$attachment->original_filename.'"',
            ]
        );
    }

    public function deleteAttachment(string $ticketIdentifier, string $attachmentIdentifier, User $actor): void
    {
        DB::transaction(function () use ($ticketIdentifier, $attachmentIdentifier, $actor): void {
            $attachment = $this->resolveAttachment($ticketIdentifier, $attachmentIdentifier);
            $attachment->delete();
            $this->ticketRepository->updateTicket($attachment->ticket, ['updated_by' => $actor->id]);
        });
    }

    public function deleteMessage(string $ticketIdentifier, string $messageIdentifier, User $actor): void
    {
        DB::transaction(function () use ($ticketIdentifier, $messageIdentifier, $actor): void {
            $ticket = $this->ticketRepository->findByIdentifierOrFail($ticketIdentifier);
            $message = $this->messageRepository->findByIdentifierOrFail($messageIdentifier);

            if ((int) $message->support_ticket_id !== (int) $ticket->id) {
                throw new ApiException('Message does not belong to this ticket.', 422);
            }

            $message->delete();
            $this->ticketRepository->updateTicket($ticket, ['updated_by' => $actor->id]);
        });
    }

    protected function storeAttachment(
        SupportTicket $ticket,
        ?SupportTicketMessage $message,
        UploadedFile $file,
        User $actor,
        ?string $forcedType = null
    ): SupportTicketAttachment {
        $this->assertValidUpload($file);

        $disk = (string) config('filesystems.support_attachments_disk', 'local');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $type = SupportTicketAttachmentType::tryFrom((string) $forcedType)
            ?? SupportTicketAttachmentType::fromExtension($extension);

        $storedName = sprintf('%s.%s', Str::uuid()->toString(), $extension);
        $directory = sprintf('support-attachments/%s', $ticket->uuid);
        $path = $file->storeAs($directory, $storedName, $disk);

        if (! $path) {
            throw new ApiException('Unable to store attachment.', 500);
        }

        $attachment = $this->attachmentRepository->createAttachment([
            'support_ticket_id' => $ticket->id,
            'ticket_message_id' => $message?->id,
            'attachment_type' => $type->value,
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'extension' => $extension,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $actor->id,
        ]);

        event(new SupportTicketAttachmentUploaded($attachment->loadMissing('ticket'), $actor));

        return $attachment;
    }

    protected function resolveAttachment(string $ticketIdentifier, string $attachmentIdentifier): SupportTicketAttachment
    {
        $ticket = $this->ticketRepository->findByIdentifierOrFail($ticketIdentifier);
        $attachment = $this->attachmentRepository->findByIdentifierOrFail($attachmentIdentifier);

        if ((int) $attachment->support_ticket_id !== (int) $ticket->id) {
            throw new ApiException('Attachment does not belong to this ticket.', 404);
        }

        return $attachment->loadMissing('ticket');
    }

    protected function assertValidUpload(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if ($extension === '' || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new ApiException('Unsupported attachment type.', 422);
        }

        $maxBytes = self::MAX_UPLOAD_KB * 1024;
        if (($file->getSize() ?: 0) > $maxBytes) {
            throw new ApiException('Attachment exceeds the maximum allowed size of 100 MB.', 422);
        }
    }

    protected function assertFileExists(SupportTicketAttachment $attachment): void
    {
        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            throw new ApiException('Attachment file is missing from storage.', 404);
        }
    }
}
