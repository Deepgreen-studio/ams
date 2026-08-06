<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerApplicationAssigned;
use App\Domains\Customers\Events\CustomerApplicationDeleted;
use App\Domains\Customers\Events\CustomerApplicationRestored;
use App\Domains\Customers\Events\CustomerApplicationUpdated;

/** Placeholder for future customer application notification workflows. */
class PrepareCustomerApplicationNotifications
{
    public function handleCustomerApplicationAssigned(CustomerApplicationAssigned $event): void {}

    public function handleCustomerApplicationUpdated(CustomerApplicationUpdated $event): void {}

    public function handleCustomerApplicationDeleted(CustomerApplicationDeleted $event): void {}

    public function handleCustomerApplicationRestored(CustomerApplicationRestored $event): void {}
}
