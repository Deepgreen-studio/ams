<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Events\NotificationPreferencesUpdated;
use App\Domains\Notifications\Repositories\NotificationPreferenceRepository;
use App\Models\User;

class NotificationPreferenceService
{
    public function __construct(
        private readonly NotificationPreferenceRepository $preferenceRepository,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(User $user): array
    {
        $stored = $this->preferenceRepository->forUser($user)
            ->keyBy(fn ($pref) => $pref->event_key->value);

        return collect(NotificationEventKey::cases())->map(function (NotificationEventKey $event) use ($stored) {
            $pref = $stored->get($event->value);

            return [
                'event_key' => $event->value,
                'event_label' => $event->label(),
                'description' => $event->description(),
                'email_enabled' => $pref?->email_enabled ?? true,
                'in_app_enabled' => $pref?->in_app_enabled ?? true,
                'sms_enabled' => $pref?->sms_enabled ?? false,
                'push_enabled' => $pref?->push_enabled ?? false,
                'whatsapp_enabled' => $pref?->whatsapp_enabled ?? false,
                'slack_enabled' => $pref?->slack_enabled ?? false,
                'teams_enabled' => $pref?->teams_enabled ?? false,
                'webhook_enabled' => $pref?->webhook_enabled ?? false,
                'uuid' => $pref?->uuid,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, bool>
     */
    public function forUserEvent(User $user, NotificationEventKey $eventKey): array
    {
        $pref = $this->preferenceRepository->forUserEvent($user, $eventKey->value);

        return [
            'email_enabled' => $pref?->email_enabled ?? true,
            'in_app_enabled' => $pref?->in_app_enabled ?? true,
            'sms_enabled' => $pref?->sms_enabled ?? false,
            'push_enabled' => $pref?->push_enabled ?? false,
            'whatsapp_enabled' => $pref?->whatsapp_enabled ?? false,
            'slack_enabled' => $pref?->slack_enabled ?? false,
            'teams_enabled' => $pref?->teams_enabled ?? false,
            'webhook_enabled' => $pref?->webhook_enabled ?? false,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function syncForUser(User $user, array $items): array
    {
        foreach ($items as $item) {
            $eventKey = NotificationEventKey::from((string) $item['event_key']);

            $this->preferenceRepository->upsertForUserEvent($user, $eventKey->value, [
                'email_enabled' => (bool) ($item['email_enabled'] ?? true),
                'in_app_enabled' => (bool) ($item['in_app_enabled'] ?? true),
                'sms_enabled' => (bool) ($item['sms_enabled'] ?? false),
                'push_enabled' => (bool) ($item['push_enabled'] ?? false),
                'whatsapp_enabled' => (bool) ($item['whatsapp_enabled'] ?? false),
                'slack_enabled' => (bool) ($item['slack_enabled'] ?? false),
                'teams_enabled' => (bool) ($item['teams_enabled'] ?? false),
                'webhook_enabled' => (bool) ($item['webhook_enabled'] ?? false),
            ]);
        }

        event(new NotificationPreferencesUpdated($user));

        return $this->listForUser($user);
    }

    public function isChannelEnabledForUser(User $user, NotificationEventKey $eventKey, NotificationChannelEnum $channel): bool
    {
        $prefs = $this->forUserEvent($user, $eventKey);

        return (bool) ($prefs[$channel->preferenceKey()] ?? false);
    }
}
