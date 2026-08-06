<?php

namespace App\Domains\Support\Controllers;

use App\Domains\Support\Requests\StorePortalSupportTicketRequest;
use App\Domains\Support\Requests\StorePortalSupportTicketReplyRequest;
use App\Domains\Support\Resources\SupportTicketCollection;
use App\Domains\Support\Resources\SupportTicketMessageResource;
use App\Domains\Support\Resources\SupportTicketResource;
use App\Domains\Support\Services\PortalSupportTicketService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalSupportTicketController
{
    public function __construct(
        private readonly PortalSupportTicketService $portalSupportTicketService,
    ) {}

    public function profile(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success(
            $this->portalSupportTicketService->profile($actor)
        );
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $paginator = $this->portalSupportTicketService->listTickets($actor, $request->query());

        return ApiResponse::success([
            'tickets' => (new SupportTicketCollection($paginator))->resolve(),
        ]);
    }

    public function store(StorePortalSupportTicketRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $ticket = $this->portalSupportTicketService->createTicket($request->validated(), $actor);

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($ticket->load([
                'company:id,uuid,company_name',
                'customer:id,uuid,first_name,last_name,company_name,email',
                'assignee:id,uuid,full_name,email',
            ])),
        ], 'Support ticket submitted successfully.', 201);
    }

    public function show(Request $request, string $ticket): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->portalSupportTicketService->findOwnedTicket($ticket, $actor);

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($model),
        ]);
    }

    public function messages(Request $request, string $ticket): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $conversation = $this->portalSupportTicketService->conversation($ticket, $actor);

        return ApiResponse::success([
            'messages' => SupportTicketMessageResource::collection($conversation['messages'])->resolve(),
            'unread_count' => $conversation['unread_count'],
            'attachment_count' => $conversation['attachment_count'],
        ]);
    }

    public function reply(StorePortalSupportTicketReplyRequest $request, string $ticket): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $message = $this->portalSupportTicketService->reply(
            $ticket,
            $request->validated(),
            $actor,
            array_values($files)
        );

        return ApiResponse::success([
            'message' => new SupportTicketMessageResource($message),
        ], 'Reply posted successfully.', 201);
    }
}
