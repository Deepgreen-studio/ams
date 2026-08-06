<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Events\NotificationCreated;
use App\Domains\Notifications\Events\NotificationDeleted;
use App\Domains\Notifications\Events\NotificationRead;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Repositories\NotificationRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->notificationRepository->paginateForUser($user, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->notificationRepository->paginateFiltered($filters);
    }

    /**
     * @return Collection<int, Notification>
     */
    public function recent(User $user, int $limit = 8): Collection
    {
        return $this->notificationRepository->recentForUser($user, $limit);
    }

    public function unreadCount(User $user): int
    {
        return $this->notificationRepository->unreadCountForUser($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?int $companyId = null): array
    {
        return $this->notificationRepository->dashboardStatistics($companyId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Notification
    {
        return DB::transaction(function () use ($data, $actor): Notification {
            $channel = NotificationChannelEnum::from((string) $data['channel']);
            $status = NotificationStatus::from((string) ($data['status'] ?? NotificationStatus::Queued->value));
            $priority = NotificationPriority::from((string) ($data['priority'] ?? NotificationPriority::Normal->value));

            /** @var Notification $notification */
            $notification = $this->notificationRepository->create([
                'company_id' => $data['company_id'] ?? null,
                'user_id' => (int) $data['user_id'],
                'channel' => $channel->value,
                'template' => $data['template'] ?? $data['event_key'] ?? null,
                'event_key' => $data['event_key'] ?? null,
                'title' => trim((string) $data['title']),
                'message' => $data['message'] ?? null,
                'status' => $status->value,
                'priority' => $priority->value,
                'laravel_notification_id' => $data['laravel_notification_id'] ?? null,
                'template_id' => $data['template_id'] ?? null,
                'data' => $data['data'] ?? [],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'sent_at' => $data['sent_at'] ?? ($status === NotificationStatus::Sent ? now() : null),
                'read_at' => $data['read_at'] ?? null,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            if ($actor) {
                event(new NotificationCreated($notification, $actor));
            }

            return $notification->fresh($this->relations());
        });
    }

    public function markRead(User $user, string $identifier): Notification
    {
        $notification = $this->notificationRepository->findByIdentifierOrFail($identifier);

        if ((int) $notification->user_id !== (int) $user->id) {
            abort(403, 'You cannot mark this notification as read.');
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
            event(new NotificationRead($notification->fresh(), $user));
        }

        return $notification->fresh($this->relations());
    }

    public function markAllRead(User $user): int
    {
        $count = $this->notificationRepository->markAllReadForUser($user);
        event(new NotificationRead(null, $user, $count));

        return $count;
    }

    public function delete(User $user, string $identifier): void
    {
        $notification = $this->notificationRepository->findByIdentifierOrFail($identifier);

        if ((int) $notification->user_id !== (int) $user->id && ! $user->can('notifications.delete')) {
            abort(403, 'You cannot delete this notification.');
        }

        $this->notificationRepository->delete($notification->id);
        event(new NotificationDeleted($notification, $user));
    }

    /**
     * @return list<string>
     */
    private function relations(): array
    {
        return [
            'company:id,uuid,company_name',
            'user:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
        ];
    }
}
