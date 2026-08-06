<?php

namespace App\Domains\Customers\Listeners;

/**
 * Hooks for reminder / email / task notifications (future phase).
 */
class PrepareCustomerCommunicationCenterNotifications
{
    public function handleCustomerNoteCreated(object $event): void {}

    public function handleCustomerNoteUpdated(object $event): void {}

    public function handleCustomerNoteDeleted(object $event): void {}

    public function handleCustomerNoteRestored(object $event): void {}

    public function handleCustomerTaskCreated(object $event): void {}

    public function handleCustomerTaskUpdated(object $event): void {}

    public function handleCustomerTaskCompleted(object $event): void {}

    public function handleCustomerTaskDeleted(object $event): void {}

    public function handleCustomerTaskRestored(object $event): void {}

    public function handleCustomerCommunicationCreated(object $event): void {}

    public function handleCustomerCommunicationUpdated(object $event): void {}

    public function handleCustomerCommunicationDeleted(object $event): void {}

    public function handleCustomerCommunicationRestored(object $event): void {}
}
