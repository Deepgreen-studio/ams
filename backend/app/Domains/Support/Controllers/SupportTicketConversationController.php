<?php

namespace App\Domains\Support\Controllers;

use App\Domains\Support\Requests\MarkSupportTicketMessagesReadRequest;
use App\Domains\Support\Requests\StoreSupportTicketAttachmentRequest;
use App\Domains\Support\Requests\StoreSupportTicketMessageRequest;
use App\Domains\Support\Resources\SupportTicketAttachmentResource;
use App\Domains\Support\Resources\SupportTicketMessageResource;
use App\Domains\Support\Services\SupportTicketConversationService;
use App\Domains\Support\Services\SupportTicketService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketConversationController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SupportTicketConversationService $conversationService,
        private readonly SupportTicketService $ticketService,
    ) {}

    public function index(Request $request, string $ticket): JsonResponse
    {
        $model = $this->ticketService->find($ticket);
        $this->authorize('view', $model);

        /** @var User $viewer */
        $viewer = $request->user();
        $result = $this->conversationService->conversation($ticket, $viewer);

        return ApiResponse::success([
            'messages' => SupportTicketMessageResource::collection($result['messages'])->resolve(),
            'unread_count' => $result['unread_count'],
            'attachment_count' => $result['attachment_count'],
        ]);
    }

    public function store(StoreSupportTicketMessageRequest $request, string $ticket): JsonResponse
    {
        $model = $this->ticketService->find($ticket);
        $this->authorize('reply', $model);

        /** @var User $actor */
        $actor = $request->user();
        $files = $request->file('attachments', []) ?: [];
        if (! is_array($files)) {
            $files = [$files];
        }

        $message = $this->conversationService->createMessage(
            $ticket,
            $request->validated(),
            $actor,
            array_values($files)
        );

        return ApiResponse::success([
            'message' => new SupportTicketMessageResource($message),
        ], 'Message posted successfully.', 201);
    }

    public function destroy(Request $request, string $ticket, string $message): JsonResponse
    {
        $model = $this->ticketService->find($ticket);
        $this->authorize('update', $model);

        /** @var User $actor */
        $actor = $request->user();
        $this->conversationService->deleteMessage($ticket, $message, $actor);

        return ApiResponse::success(null, 'Message deleted successfully.');
    }

    public function markRead(MarkSupportTicketMessagesReadRequest $request, string $ticket): JsonResponse
    {
        $model = $this->ticketService->find($ticket);
        $this->authorize('view', $model);

        /** @var User $viewer */
        $viewer = $request->user();
        $count = $this->conversationService->markRead(
            $ticket,
            $viewer,
            $request->validated('message_ids')
        );

        return ApiResponse::success([
            'marked' => $count,
        ], 'Messages marked as read.');
    }

    public function storeAttachments(StoreSupportTicketAttachmentRequest $request, string $ticket): JsonResponse
    {
        $model = $this->ticketService->find($ticket);
        $this->authorize('reply', $model);

        /** @var User $actor */
        $actor = $request->user();
        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = [$files];
        }

        $attachments = $this->conversationService->uploadAttachments(
            $ticket,
            array_values($files),
            $actor,
            $request->validated('message_id'),
            $request->validated('attachment_type')
        );

        return ApiResponse::success([
            'attachments' => SupportTicketAttachmentResource::collection($attachments)->resolve(),
        ], 'Attachments uploaded successfully.', 201);
    }

    public function download(string $ticket, string $attachment): StreamedResponse
    {
        $model = $this->ticketService->find($ticket);
        $this->authorize('view', $model);

        return $this->conversationService->downloadAttachment($ticket, $attachment);
    }

    public function preview(string $ticket, string $attachment): StreamedResponse
    {
        $model = $this->ticketService->find($ticket);
        $this->authorize('view', $model);

        return $this->conversationService->previewAttachment($ticket, $attachment);
    }

    public function destroyAttachment(Request $request, string $ticket, string $attachment): JsonResponse
    {
        $model = $this->ticketService->find($ticket);
        $this->authorize('update', $model);

        /** @var User $actor */
        $actor = $request->user();
        $this->conversationService->deleteAttachment($ticket, $attachment, $actor);

        return ApiResponse::success(null, 'Attachment deleted successfully.');
    }
}
