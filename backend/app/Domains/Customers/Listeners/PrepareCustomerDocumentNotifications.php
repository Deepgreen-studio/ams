<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerDocumentDeleted;
use App\Domains\Customers\Events\CustomerDocumentRestored;
use App\Domains\Customers\Events\CustomerDocumentUpdated;
use App\Domains\Customers\Events\CustomerDocumentUploaded;
use App\Domains\Customers\Events\CustomerDocumentVersionUploaded;

/**
 * Hooks for document expiry / compliance notifications (future phase).
 */
class PrepareCustomerDocumentNotifications
{
    public function handleCustomerDocumentUploaded(CustomerDocumentUploaded $event): void {}

    public function handleCustomerDocumentVersionUploaded(CustomerDocumentVersionUploaded $event): void {}

    public function handleCustomerDocumentUpdated(CustomerDocumentUpdated $event): void {}

    public function handleCustomerDocumentDeleted(CustomerDocumentDeleted $event): void {}

    public function handleCustomerDocumentRestored(CustomerDocumentRestored $event): void {}
}
