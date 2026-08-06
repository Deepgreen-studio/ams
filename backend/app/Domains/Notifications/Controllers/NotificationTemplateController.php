<?php

namespace App\Domains\Notifications\Controllers;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationPermission;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Domains\Notifications\Requests\IndexNotificationTemplateRequest;
use App\Domains\Notifications\Requests\NotificationTemplateWorkflowRequest;
use App\Domains\Notifications\Requests\PreviewNotificationTemplateRequest;
use App\Domains\Notifications\Requests\StoreNotificationTemplateRequest;
use App\Domains\Notifications\Requests\TestSendNotificationTemplateRequest;
use App\Domains\Notifications\Requests\UpdateNotificationTemplateRequest;
use App\Domains\Notifications\Resources\NotificationTemplateApprovalResource;
use App\Domains\Notifications\Resources\NotificationTemplateResource;
use App\Domains\Notifications\Resources\NotificationTemplateVersionResource;
use App\Domains\Notifications\Services\NotificationTemplateService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationTemplateService $templateService,
    ) {}

    public function index(IndexNotificationTemplateRequest $request): JsonResponse
    {
        $this->authorize('viewAny', NotificationTemplate::class);
        $paginator = $this->templateService->paginate($request->filters());

        return ApiResponse::success([
            'templates' => [
                'items' => NotificationTemplateResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            'events' => collect(NotificationEventKey::cases())->map(fn (NotificationEventKey $event) => [
                'value' => $event->value,
                'label' => $event->label(),
                'variables' => $event->defaultVariables(),
            ])->values()->all(),
            'channels' => collect(NotificationChannelEnum::cases())->map(fn (NotificationChannelEnum $channel) => [
                'value' => $channel->value,
                'label' => $channel->label(),
                'implemented' => $channel->isImplemented(),
            ])->values()->all(),
            'locales' => $this->templateService->locales(),
            'priorities' => NotificationPriority::values(),
            'workflow_statuses' => collect(NotificationTemplateStatus::cases())->map(fn (NotificationTemplateStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
        ]);
    }

    public function show(string $template): JsonResponse
    {
        $model = $this->templateService->find($template);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'template' => new NotificationTemplateResource($model),
            'locales' => $this->templateService->locales(),
            'channels' => NotificationChannelEnum::values(),
        ]);
    }

    public function store(StoreNotificationTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', NotificationTemplate::class);

        /** @var User $actor */
        $actor = $request->user();
        $template = $this->templateService->create($request->validated(), $actor);

        return ApiResponse::success([
            'template' => new NotificationTemplateResource($template),
        ], 'Notification template created.', 201);
    }

    public function update(UpdateNotificationTemplateRequest $request, string $template): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->templateService->update($template, $request->validated(), $actor);

        return ApiResponse::success([
            'template' => new NotificationTemplateResource($updated),
        ], 'Notification template updated.');
    }

    public function destroy(string $template): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('delete', $existing);
        $this->templateService->delete($template, request()->user());

        return ApiResponse::success(null, 'Notification template deleted.');
    }

    public function preview(PreviewNotificationTemplateRequest $request, string $template): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('view', $existing);

        return ApiResponse::success([
            'preview' => $this->templateService->preview($template, $request->validated('variables') ?? []),
        ]);
    }

    public function testSend(TestSendNotificationTemplateRequest $request, string $template): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->templateService->testSend($template, $actor, $request->validated());

        return ApiResponse::success($result, 'Test notification sent.');
    }

    public function versions(string $template): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('view', $existing);
        $versions = $this->templateService->versions($template);

        return ApiResponse::success([
            'template' => [
                'uuid' => $existing->uuid,
                'name' => $existing->name,
                'current_version' => $existing->current_version,
                'workflow_status' => $existing->workflow_status?->value,
            ],
            'versions' => NotificationTemplateVersionResource::collection($versions)->resolve(),
        ]);
    }

    public function showVersion(string $template, string $version): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('view', $existing);

        return ApiResponse::success([
            'version' => new NotificationTemplateVersionResource(
                $this->templateService->showVersion($template, $version)
            ),
        ]);
    }

    public function compare(Request $request, string $template): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('view', $existing);

        $from = (string) $request->query('from');
        $to = (string) $request->query('to');
        if ($from === '' || $to === '') {
            return ApiResponse::error('Query parameters from and to are required.', 422);
        }

        $result = $this->templateService->compare($template, $from, $to);

        return ApiResponse::success([
            'from' => new NotificationTemplateVersionResource($result['from']),
            'to' => new NotificationTemplateVersionResource($result['to']),
            'comparison' => $result['comparison'],
        ]);
    }

    public function restoreVersion(NotificationTemplateWorkflowRequest $request, string $template, string $version): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->templateService->restoreVersion(
            $template,
            $version,
            $actor,
            $request->validated('reason')
        );

        return ApiResponse::success([
            'template' => new NotificationTemplateResource($restored),
        ], 'Template restored to a new draft version.');
    }

    public function submit(NotificationTemplateWorkflowRequest $request, string $template): JsonResponse
    {
        $existing = $this->templateService->find($template);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->templateService->submitForReview($template, $actor, $request->validated());

        return ApiResponse::success([
            'template' => new NotificationTemplateResource($updated),
        ], 'Template submitted for review.');
    }

    public function publish(string $template): JsonResponse
    {
        $existing = $this->templateService->find($template);
        abort_unless(request()->user()?->can(NotificationPermission::PUBLISH), 403);

        /** @var User $actor */
        $actor = request()->user();
        $updated = $this->templateService->publish($template, $actor);

        return ApiResponse::success([
            'template' => new NotificationTemplateResource($updated),
        ], 'Template published.');
    }

    public function approvals(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(NotificationPermission::APPROVE)
            || $request->user()?->can(NotificationPermission::VIEW), 403);

        $paginator = $this->templateService->paginateApprovals($request->query());

        return ApiResponse::success([
            'approvals' => [
                'items' => NotificationTemplateApprovalResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function approve(NotificationTemplateWorkflowRequest $request, string $approval): JsonResponse
    {
        abort_unless($request->user()?->can(NotificationPermission::APPROVE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $template = $this->templateService->approve($approval, $actor, $request->validated());

        return ApiResponse::success([
            'template' => new NotificationTemplateResource($template),
        ], 'Template approved.');
    }

    public function reject(NotificationTemplateWorkflowRequest $request, string $approval): JsonResponse
    {
        abort_unless($request->user()?->can(NotificationPermission::APPROVE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $template = $this->templateService->reject($approval, $actor, $request->validated());

        return ApiResponse::success([
            'template' => new NotificationTemplateResource($template),
        ], 'Template rejected.');
    }
}
