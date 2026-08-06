<?php

namespace App\Domains\Notifications\Controllers;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationChannel;
use App\Domains\Notifications\Requests\IndexNotificationRequest;
use App\Domains\Notifications\Requests\StoreNotificationRequest;
use App\Domains\Notifications\Requests\SyncNotificationPreferencesRequest;
use App\Domains\Notifications\Requests\UpdateNotificationChannelRequest;
use App\Domains\Notifications\Resources\NotificationChannelResource;
use App\Domains\Notifications\Resources\NotificationCollection;
use App\Domains\Notifications\Resources\NotificationDeliveryLogResource;
use App\Domains\Notifications\Resources\NotificationResource;
use App\Domains\Notifications\Services\NotificationChannelService;
use App\Domains\Notifications\Services\NotificationDeliveryLogService;
use App\Domains\Notifications\Services\NotificationPreferenceService;
use App\Domains\Notifications\Services\NotificationService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationCenterController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly NotificationPreferenceService $preferenceService,
        private readonly NotificationDeliveryLogService $deliveryLogService,
        private readonly NotificationChannelService $channelService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success([
            'statistics' => $this->notificationService->dashboard(),
            'unread_count' => $this->notificationService->unreadCount($user),
            'recent' => NotificationResource::collection(
                $this->notificationService->recent($user)
            )->resolve(),
            'channels' => NotificationChannelResource::collection(
                $this->channelService->list()
            )->resolve(),
            'delivery_statistics' => $this->deliveryLogService->statistics(),
        ]);
    }

    public function center(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success([
            'unread_count' => $this->notificationService->unreadCount($user),
            'recent' => NotificationResource::collection(
                $this->notificationService->recent($user)
            )->resolve(),
            'channels' => $this->channelService->matrix(),
            'channel_catalog' => NotificationChannelResource::collection(
                $this->channelService->list()
            )->resolve(),
            'events' => collect(NotificationEventKey::cases())->map(fn (NotificationEventKey $event) => [
                'value' => $event->value,
                'label' => $event->label(),
                'description' => $event->description(),
            ])->values()->all(),
            'statuses' => collect(NotificationStatus::cases())->map(fn (NotificationStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
            'priorities' => collect(NotificationPriority::cases())->map(fn (NotificationPriority $priority) => [
                'value' => $priority->value,
                'label' => $priority->label(),
            ])->values()->all(),
        ]);
    }

    public function index(IndexNotificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $paginator = $this->notificationService->paginateForUser($user, $request->filters());

        return ApiResponse::success([
            'notifications' => (new NotificationCollection($paginator))->resolve(),
            'unread_count' => $this->notificationService->unreadCount($user),
        ]);
    }

    public function unread(IndexNotificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $filters = array_merge($request->filters(), ['unread' => '1', 'channel' => 'in_app']);
        $paginator = $this->notificationService->paginateForUser($user, $filters);

        return ApiResponse::success([
            'notifications' => (new NotificationCollection($paginator))->resolve(),
            'unread_count' => $this->notificationService->unreadCount($user),
        ]);
    }

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $this->authorize('create', Notification::class);

        /** @var User $actor */
        $actor = $request->user();
        $notification = $this->notificationService->create($request->validated(), $actor);

        return ApiResponse::success([
            'notification' => new NotificationResource($notification),
        ], 'Notification created successfully.', 201);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success([
            'unread_count' => $this->notificationService->unreadCount($user),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $model = $this->notificationService->markRead($user, $notification);

        return ApiResponse::success([
            'notification' => new NotificationResource($model),
            'unread_count' => $this->notificationService->unreadCount($user),
        ], 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $count = $this->notificationService->markAllRead($user);

        return ApiResponse::success([
            'marked' => $count,
            'unread_count' => 0,
        ], 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->notificationService->delete($user, $notification);

        return ApiResponse::success(null, 'Notification deleted.');
    }

    public function preferences(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success([
            'preferences' => $this->preferenceService->listForUser($user),
            'channels' => collect(NotificationChannelEnum::cases())->map(fn (NotificationChannelEnum $channel) => [
                'value' => $channel->value,
                'label' => $channel->label(),
                'implemented' => $channel->isImplemented(),
            ])->values()->all(),
        ]);
    }

    public function syncPreferences(SyncNotificationPreferencesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $preferences = $this->preferenceService->syncForUser($user, $request->validated('preferences'));

        return ApiResponse::success([
            'preferences' => $preferences,
        ], 'Notification preferences updated.');
    }

    public function channels(): JsonResponse
    {
        $this->authorize('viewAny', NotificationChannel::class);

        return ApiResponse::success([
            'channels' => NotificationChannelResource::collection(
                $this->channelService->list()
            )->resolve(),
            'matrix' => $this->channelService->matrix(),
        ]);
    }

    public function updateChannel(UpdateNotificationChannelRequest $request, string $channel): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->channelService->list()->firstWhere('uuid', $channel)
            ?? $this->channelService->list()->firstWhere('key', $channel);

        if ($model) {
            $this->authorize('update', $model);
        }

        $updated = $this->channelService->update($channel, $request->validated(), $actor);

        return ApiResponse::success([
            'channel' => new NotificationChannelResource($updated),
        ], 'Notification channel updated.');
    }

    public function deliveryLogs(Request $request): JsonResponse
    {
        $this->authorize('viewLogs', Notification::class);

        $paginator = $this->deliveryLogService->paginate($request->query());

        return ApiResponse::success([
            'statistics' => $this->deliveryLogService->statistics(),
            'logs' => [
                'items' => NotificationDeliveryLogResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }
}
