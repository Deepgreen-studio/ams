<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\LicenseCreated;
use App\Domains\Customers\Events\LicenseDeleted;
use App\Domains\Customers\Events\LicenseRestored;
use App\Domains\Customers\Events\LicenseRevoked;
use App\Domains\Customers\Events\LicenseUpdated;

/**
 * Hooks for license lifecycle notifications (future phase).
 */
class PrepareLicenseNotifications
{
    public function handleLicenseCreated(LicenseCreated $event): void {}

    public function handleLicenseUpdated(LicenseUpdated $event): void {}

    public function handleLicenseRevoked(LicenseRevoked $event): void {}

    public function handleLicenseDeleted(LicenseDeleted $event): void {}

    public function handleLicenseRestored(LicenseRestored $event): void {}
}
