<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerContactCreated;
use App\Domains\Customers\Events\CustomerContactDeleted;
use App\Domains\Customers\Events\CustomerContactRestored;
use App\Domains\Customers\Events\CustomerContactUpdated;

/** Placeholder for future customer contact notification workflows. */
class PrepareCustomerContactNotifications
{
    public function handleCustomerContactCreated(CustomerContactCreated $event): void {}

    public function handleCustomerContactUpdated(CustomerContactUpdated $event): void {}

    public function handleCustomerContactDeleted(CustomerContactDeleted $event): void {}

    public function handleCustomerContactRestored(CustomerContactRestored $event): void {}
}
