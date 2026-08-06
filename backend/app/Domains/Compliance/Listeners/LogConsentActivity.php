<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\ConsentGranted;
use App\Domains\Compliance\Events\ConsentUpdated;
use App\Domains\Compliance\Events\ConsentWithdrawn;

class LogConsentActivity
{
    public function handleConsentGranted(ConsentGranted $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->consent)
            ->withProperties([
                'event' => 'consent_granted',
                'consent_type_id' => $event->consent->consent_type_id,
                'subject_email' => $event->consent->subject_email,
                'version' => $event->consent->consent_version,
                'source' => $event->consent->source?->value,
            ])
            ->log('Consent granted');
    }

    public function handleConsentWithdrawn(ConsentWithdrawn $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->consent)
            ->withProperties([
                'event' => 'consent_withdrawn',
                'consent_type_id' => $event->consent->consent_type_id,
                'subject_email' => $event->consent->subject_email,
                'version' => $event->consent->consent_version,
                'source' => $event->consent->source?->value,
            ])
            ->log('Consent withdrawn');
    }

    public function handleConsentUpdated(ConsentUpdated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->consent)
            ->withProperties([
                'event' => 'consent_updated',
                'consent_type_id' => $event->consent->consent_type_id,
                'status' => $event->consent->status?->value,
                'granted' => $event->consent->granted,
            ])
            ->log('Consent updated');
    }
}
