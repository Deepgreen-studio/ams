<?php

namespace App\Domains\Support\Controllers;

use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Requests\AssignSupportTicketRequest;
use App\Domains\Support\Requests\IndexSupportTicketRequest;
use App\Domains\Support\Requests\StoreSupportTicketRequest;
use App\Domains\Support\Requests\TransitionSupportTicketRequest;
use App\Domains\Support\Requests\UpdateSupportTicketRequest;
use App\Domains\Support\Resources\SupportTicketCollection;
use App\Domains\Support\Resources\SupportTicketResource;
use App\Domains\Support\Resources\SupportTicketStatusHistoryResource;
use App\Domains\Support\Services\SupportTicketAssignmentService;
use App\Domains\Support\Services\SupportTicketService;
use App\Domains\Support\Services\SupportTicketWorkflowService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SupportTicketService $supportTicketService,
        private readonly SupportTicketWorkflowService $workflowService,
        private readonly SupportTicketAssignmentService $assignmentService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $result = $this->supportTicketService->dashboard($request->query('company'));

        return ApiResponse::success([
            'statistics' => $result['statistics'],
            'recent_open' => (new SupportTicketCollection($result['recent_open']))->resolve(),
            'urgent' => (new SupportTicketCollection($result['urgent']))->resolve(),
        ]);
    }

    public function board(IndexSupportTicketRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $result = $this->workflowService->kanban($request->filters());
        $columns = array_map(function (array $column): array {
            return [
                'status' => $column['status'],
                'label' => $column['label'],
                'count' => $column['count'],
                'tickets' => SupportTicketResource::collection($column['tickets'])->resolve(),
            ];
        }, $result['columns']);

        return ApiResponse::success([
            'columns' => $columns,
            'statistics' => $result['statistics'],
        ]);
    }

    public function queue(IndexSupportTicketRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $filters = $request->filters();
        if (($filters['queue'] ?? null) === 'mine') {
            /** @var User $user */
            $user = $request->user();
            $filters['assigned_to'] = $user->id;
        }

        $result = $this->workflowService->queue($filters);

        return ApiResponse::success([
            'tickets' => (new SupportTicketCollection($result['tickets']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function index(IndexSupportTicketRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $result = $this->supportTicketService->list($request->filters());

        return ApiResponse::success([
            'tickets' => (new SupportTicketCollection($result['tickets']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $this->authorize('create', SupportTicket::class);

        /** @var User $actor */
        $actor = $request->user();
        $ticket = $this->supportTicketService->create($request->validated(), $actor);

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($ticket),
        ], 'Support ticket created successfully.', 201);
    }

    public function show(string $ticket): JsonResponse
    {
        $model = $this->supportTicketService->show($ticket);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($model),
        ]);
    }

    public function timeline(string $ticket): JsonResponse
    {
        $model = $this->supportTicketService->find($ticket);
        $this->authorize('view', $model);

        $history = $this->workflowService->timeline($ticket);

        return ApiResponse::success([
            'timeline' => SupportTicketStatusHistoryResource::collection($history)->resolve(),
        ]);
    }

    public function update(UpdateSupportTicketRequest $request, string $ticket): JsonResponse
    {
        $existing = $this->supportTicketService->find($ticket);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->supportTicketService->update($ticket, $request->validated(), $actor);

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($updated),
        ], 'Support ticket updated successfully.');
    }

    public function transition(TransitionSupportTicketRequest $request, string $ticket): JsonResponse
    {
        $existing = $this->supportTicketService->find($ticket);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->workflowService->transition(
            $ticket,
            $request->validated('status'),
            $actor,
            $request->validated('comments')
        );

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($updated),
        ], 'Ticket status updated successfully.');
    }

    public function assign(AssignSupportTicketRequest $request, string $ticket): JsonResponse
    {
        $existing = $this->supportTicketService->find($ticket);
        $this->authorize('assign', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->supportTicketService->assign($ticket, $request->validated(), $actor);

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($updated),
        ], 'Support ticket assigned successfully.');
    }

    public function agents(): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $agents = $this->assignmentService->availableAgents()->map(fn (User $user) => [
            'uuid' => $user->uuid,
            'full_name' => $user->full_name,
            'email' => $user->email,
        ])->values();

        return ApiResponse::success([
            'agents' => $agents,
        ]);
    }

    public function close(Request $request, string $ticket): JsonResponse
    {
        $existing = $this->supportTicketService->find($ticket);
        $this->authorize('close', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->supportTicketService->close(
            $ticket,
            $actor,
            $request->input('comments')
        );

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($updated),
        ], 'Support ticket closed successfully.');
    }

    public function reopen(Request $request, string $ticket): JsonResponse
    {
        $existing = $this->supportTicketService->find($ticket);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->supportTicketService->reopen(
            $ticket,
            $actor,
            $request->input('comments')
        );

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($updated),
        ], 'Support ticket reopened successfully.');
    }

    public function destroy(Request $request, string $ticket): JsonResponse
    {
        $existing = $this->supportTicketService->find($ticket);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->supportTicketService->delete($ticket, $actor);

        return ApiResponse::success(null, 'Support ticket archived successfully.');
    }

    public function restore(Request $request, string $ticket): JsonResponse
    {
        $existing = $this->supportTicketService->find($ticket, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->supportTicketService->restore($ticket, $actor);

        return ApiResponse::success([
            'ticket' => new SupportTicketResource($restored),
        ], 'Support ticket restored successfully.');
    }
}
