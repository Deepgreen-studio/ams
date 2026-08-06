<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationDeliveryStatus;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Notifications\Notifications\TemplatedNotification;
use App\Domains\Notifications\Repositories\NotificationLogRepository;
use App\Domains\Notifications\Repositories\NotificationRepository;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

/**
 * Existing dispatch bridge used by domain listeners.
 * Phase 8.1 keeps this operational; automation rules are out of scope.
 */
class NotificationDispatchService
{
    public function __construct(
        private readonly NotificationChannelResolver $channelResolver,
        private readonly NotificationTemplateService $templateService,
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationLogRepository $logRepository,
    ) {}

    /**
     * @param  Collection<int, User>|iterable<User>  $recipients
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(NotificationEventKey $eventKey, iterable $recipients, array $payload = []): int
    {
        $sent = 0;
        $unique = collect($recipients)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();

        foreach ($unique as $user) {
            $sent += $this->dispatchToUser($eventKey, $user, $payload) ? 1 : 0;
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchToUser(NotificationEventKey $eventKey, User $user, array $payload = []): bool
    {
        $variables = $this->buildVariables($user, $payload);
        $channels = $this->channelResolver->resolveForUser($user, $eventKey);

        if ($channels === []) {
            $this->logSkipped($user, $eventKey, NotificationChannelEnum::Email, $variables, 'All channels disabled by preference or global settings.');

            return false;
        }

        $laravelChannels = [];
        $mailSubject = null;
        $mailBody = null;
        $inAppTitle = null;
        $inAppBody = null;
        $createdNotifications = [];

        foreach ($channels as $channel) {
            $rendered = $this->templateService->render($eventKey, $channel, $variables);
            $laravel = $channel->laravelChannel();

            /** @var Notification $record */
            $record = $this->notificationRepository->create([
                'company_id' => $payload['company_id'] ?? null,
                'user_id' => $user->id,
                'channel' => $channel->value,
                'template' => $eventKey->value,
                'event_key' => $eventKey->value,
                'title' => $rendered['title'] ?: ($rendered['subject'] ?: $eventKey->label()),
                'message' => $rendered['body'],
                'status' => NotificationStatus::Queued->value,
                'priority' => $this->resolvePriority($payload['priority'] ?? null)->value,
                'template_id' => $this->templateService->resolve($eventKey, $channel)?->id,
                'data' => $variables,
                'sent_at' => null,
                'created_by' => $payload['actor_id'] ?? null,
                'updated_by' => $payload['actor_id'] ?? null,
            ]);
            $createdNotifications[] = $record;

            if ($laravel === null) {
                $this->logSkipped($user, $eventKey, $channel, $variables, 'Channel reserved for future release.', $record->id);
                $this->notificationRepository->update($record->id, [
                    'status' => NotificationStatus::Cancelled->value,
                ]);

                continue;
            }

            $laravelChannels[] = $laravel;

            if ($channel === NotificationChannelEnum::Email) {
                $mailSubject = $rendered['subject'];
                $mailBody = $rendered['body'];
            }

            if ($channel === NotificationChannelEnum::InApp) {
                $inAppTitle = $rendered['title'] ?: $eventKey->label();
                $inAppBody = $rendered['body'];
            }

            $this->logRepository->create([
                'notification_id' => $record->id,
                'company_id' => $payload['company_id'] ?? null,
                'event_key' => $eventKey->value,
                'channel' => $channel->value,
                'status' => NotificationDeliveryStatus::Queued->value,
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->getKey(),
                'recipient' => $channel === NotificationChannelEnum::Email ? $user->email : (string) $user->id,
                'subject' => $rendered['subject'] ?? $rendered['title'],
                'body_preview' => Str::limit(strip_tags((string) $rendered['body']), 240),
                'payload' => $variables,
                'queued_at' => now(),
            ]);
        }

        $laravelChannels = array_values(array_unique($laravelChannels));
        if ($laravelChannels === []) {
            return false;
        }

        $databaseData = [
            'type' => $eventKey->value,
            'event_key' => $eventKey->value,
            'ticket_uuid' => $variables['ticket_uuid'] ?? null,
            'ticket_number' => $variables['ticket_number'] ?? null,
            'subject' => $variables['subject'] ?? null,
            'priority' => $variables['priority'] ?? null,
            'status' => $variables['status'] ?? null,
            'actor_name' => $variables['actor_name'] ?? null,
            'ticket_url' => $variables['ticket_url'] ?? null,
            'from_status' => $variables['from_status'] ?? null,
            'to_status' => $variables['to_status'] ?? null,
            'sla_metric' => $variables['sla_metric'] ?? null,
            'escalation_level' => $variables['escalation_level'] ?? null,
        ];

        NotificationFacade::send($user, new TemplatedNotification(
            eventKey: $eventKey,
            laravelChannels: $laravelChannels,
            payload: $variables,
            databaseData: $databaseData,
            mailSubject: $mailSubject,
            mailBody: $mailBody,
            inAppTitle: $inAppTitle,
            inAppBody: $inAppBody,
        ));

        foreach ($createdNotifications as $record) {
            if ($record->channel->laravelChannel() === null) {
                continue;
            }

            $this->notificationRepository->update($record->id, [
                'status' => NotificationStatus::Sent->value,
                'sent_at' => now(),
            ]);
        }

        NotificationLog::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('event_key', $eventKey->value)
            ->where('status', NotificationDeliveryStatus::Queued->value)
            ->where('queued_at', '>=', now()->subMinute())
            ->update([
                'status' => NotificationDeliveryStatus::Sent->value,
                'sent_at' => now(),
            ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildVariables(User $user, array $payload): array
    {
        $frontend = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $ticketUuid = $payload['ticket_uuid'] ?? null;

        return array_change_key_case(array_merge([
            'recipient_name' => $user->full_name ?? $user->name ?? '',
            'ticket_url' => $ticketUuid ? $frontend.'/support/tickets/'.$ticketUuid : null,
        ], $payload), CASE_LOWER);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function logSkipped(
        User $user,
        NotificationEventKey $eventKey,
        NotificationChannelEnum $channel,
        array $variables,
        string $reason,
        ?int $notificationId = null,
    ): void {
        $this->logRepository->create([
            'notification_id' => $notificationId,
            'event_key' => $eventKey->value,
            'channel' => $channel->value,
            'status' => NotificationDeliveryStatus::Skipped->value,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'recipient' => $user->email,
            'error_message' => $reason,
            'payload' => $variables,
            'queued_at' => now(),
        ]);
    }

    private function resolvePriority(mixed $priority): NotificationPriority
    {
        if ($priority instanceof NotificationPriority) {
            return $priority;
        }

        $value = strtolower(trim((string) ($priority ?? NotificationPriority::Normal->value)));

        return NotificationPriority::tryFrom($value) ?? NotificationPriority::Normal;
    }
}
