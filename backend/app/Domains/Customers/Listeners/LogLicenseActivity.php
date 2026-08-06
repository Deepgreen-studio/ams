<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\LicenseCreated;
use App\Domains\Customers\Events\LicenseDeleted;
use App\Domains\Customers\Events\LicenseRestored;
use App\Domains\Customers\Events\LicenseRevoked;
use App\Domains\Customers\Events\LicenseUpdated;

class LogLicenseActivity
{
    public function handleLicenseCreated(LicenseCreated $event): void
    {
        activity('licenses')
            ->causedBy($event->actor)
            ->performedOn($event->license)
            ->withProperties([
                'event' => 'license_created',
                'customer_id' => $event->license->customer_id,
                'subscription_id' => $event->license->subscription_id,
                'license_key' => $event->license->license_key,
                'status' => $event->license->status?->value ?? $event->license->status,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('License issued');
    }

    public function handleLicenseUpdated(LicenseUpdated $event): void
    {
        activity('licenses')
            ->causedBy($event->actor)
            ->performedOn($event->license)
            ->withProperties([
                'event' => 'license_updated',
                'status' => $event->license->status?->value ?? $event->license->status,
                'expires_at' => $event->license->expires_at,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('License updated');
    }

    public function handleLicenseRevoked(LicenseRevoked $event): void
    {
        activity('licenses')
            ->causedBy($event->actor)
            ->performedOn($event->license)
            ->withProperties([
                'event' => 'license_revoked',
                'status' => $event->license->status?->value ?? $event->license->status,
                'revoked_at' => $event->license->revoked_at,
                'revoked_reason' => $event->license->revoked_reason,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('License revoked');
    }

    public function handleLicenseDeleted(LicenseDeleted $event): void
    {
        activity('licenses')
            ->causedBy($event->actor)
            ->performedOn($event->license)
            ->withProperties([
                'event' => 'license_archived',
                'subscription_id' => $event->license->subscription_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('License archived');
    }

    public function handleLicenseRestored(LicenseRestored $event): void
    {
        activity('licenses')
            ->causedBy($event->actor)
            ->performedOn($event->license)
            ->withProperties([
                'event' => 'license_restored',
                'subscription_id' => $event->license->subscription_id,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('License restored');
    }
}
