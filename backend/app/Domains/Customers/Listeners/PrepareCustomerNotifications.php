<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerCreated;
use App\Domains\Customers\Events\CustomerDeleted;
use App\Domains\Customers\Events\CustomerRestored;
use App\Domains\Customers\Events\CustomerUpdated;

/** Placeholder for future customer notification workflows. */
class PrepareCustomerNotifications
{
    public function handleCustomerCreated(CustomerCreated $event): void {}

    public function handleCustomerUpdated(CustomerUpdated $event): void {}

    public function handleCustomerDeleted(CustomerDeleted $event): void {}

    public function handleCustomerRestored(CustomerRestored $event): void {}
}
