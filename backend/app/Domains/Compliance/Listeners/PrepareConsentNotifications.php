<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\ConsentGranted;
use App\Domains\Compliance\Events\ConsentUpdated;
use App\Domains\Compliance\Events\ConsentWithdrawn;
use App\Domains\Compliance\Notifications\ConsentChangedNotification;
use App\Models\User;

class PrepareConsentNotifications
{
    public function handleConsentGranted(ConsentGranted $event): void
    {
        $this->notifySubject($event->consent, $event->actor, 'granted');
    }

    public function handleConsentWithdrawn(ConsentWithdrawn $event): void
    {
        $this->notifySubject($event->consent, $event->actor, 'withdrawn');
    }

    public function handleConsentUpdated(ConsentUpdated $event): void
    {
        // Covered by grant/withdraw when status flips; reserved for future nuance.
    }

    protected function notifySubject(\App\Domains\Compliance\Models\UserConsent $consent, User $actor, string $changeType): void
    {
        if ($consent->user_id === null || $consent->user_id === $actor->id) {
            return;
        }

        /** @var User|null $subject */
        $subject = User::query()->find($consent->user_id);

        if (! $subject) {
            return;
        }

        $consent->loadMissing('consentType');
        $subject->notify(new ConsentChangedNotification($consent, $actor, $changeType));
    }
}
