<?php

namespace App\Domains\Customers\Listeners;

use App\Domains\Customers\Events\CustomerCommunicationCreated;
use App\Domains\Customers\Events\CustomerCommunicationDeleted;
use App\Domains\Customers\Events\CustomerCommunicationRestored;
use App\Domains\Customers\Events\CustomerCommunicationUpdated;
use App\Domains\Customers\Events\CustomerNoteCreated;
use App\Domains\Customers\Events\CustomerNoteDeleted;
use App\Domains\Customers\Events\CustomerNoteRestored;
use App\Domains\Customers\Events\CustomerNoteUpdated;
use App\Domains\Customers\Events\CustomerTaskCompleted;
use App\Domains\Customers\Events\CustomerTaskCreated;
use App\Domains\Customers\Events\CustomerTaskDeleted;
use App\Domains\Customers\Events\CustomerTaskRestored;
use App\Domains\Customers\Events\CustomerTaskUpdated;

class LogCustomerCommunicationCenterActivity
{
    public function handleCustomerNoteCreated(CustomerNoteCreated $event): void
    {
        activity('customer_notes')->causedBy($event->actor)->performedOn($event->note)->withProperties([
            'event' => 'customer_note_created',
            'note_type' => $event->note->note_type?->value ?? $event->note->note_type,
            'customer_id' => $event->note->customer_id,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer note created');
    }

    public function handleCustomerNoteUpdated(CustomerNoteUpdated $event): void
    {
        activity('customer_notes')->causedBy($event->actor)->performedOn($event->note)->withProperties([
            'event' => 'customer_note_updated',
            'note_type' => $event->note->note_type?->value ?? $event->note->note_type,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer note updated');
    }

    public function handleCustomerNoteDeleted(CustomerNoteDeleted $event): void
    {
        activity('customer_notes')->causedBy($event->actor)->performedOn($event->note)->withProperties([
            'event' => 'customer_note_archived',
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer note archived');
    }

    public function handleCustomerNoteRestored(CustomerNoteRestored $event): void
    {
        activity('customer_notes')->causedBy($event->actor)->performedOn($event->note)->withProperties([
            'event' => 'customer_note_restored',
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer note restored');
    }

    public function handleCustomerTaskCreated(CustomerTaskCreated $event): void
    {
        activity('customer_tasks')->causedBy($event->actor)->performedOn($event->task)->withProperties([
            'event' => 'customer_task_created',
            'status' => $event->task->status?->value ?? $event->task->status,
            'due_at' => $event->task->due_at,
            'remind_at' => $event->task->remind_at,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer task created');
    }

    public function handleCustomerTaskUpdated(CustomerTaskUpdated $event): void
    {
        activity('customer_tasks')->causedBy($event->actor)->performedOn($event->task)->withProperties([
            'event' => 'customer_task_updated',
            'status' => $event->task->status?->value ?? $event->task->status,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer task updated');
    }

    public function handleCustomerTaskCompleted(CustomerTaskCompleted $event): void
    {
        activity('customer_tasks')->causedBy($event->actor)->performedOn($event->task)->withProperties([
            'event' => 'customer_task_completed',
            'completed_at' => $event->task->completed_at,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer task completed');
    }

    public function handleCustomerTaskDeleted(CustomerTaskDeleted $event): void
    {
        activity('customer_tasks')->causedBy($event->actor)->performedOn($event->task)->withProperties([
            'event' => 'customer_task_archived',
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer task archived');
    }

    public function handleCustomerTaskRestored(CustomerTaskRestored $event): void
    {
        activity('customer_tasks')->causedBy($event->actor)->performedOn($event->task)->withProperties([
            'event' => 'customer_task_restored',
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer task restored');
    }

    public function handleCustomerCommunicationCreated(CustomerCommunicationCreated $event): void
    {
        activity('customer_communications')->causedBy($event->actor)->performedOn($event->communication)->withProperties([
            'event' => 'customer_communication_created',
            'type' => $event->communication->type?->value ?? $event->communication->type,
            'direction' => $event->communication->direction?->value ?? $event->communication->direction,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer communication logged');
    }

    public function handleCustomerCommunicationUpdated(CustomerCommunicationUpdated $event): void
    {
        activity('customer_communications')->causedBy($event->actor)->performedOn($event->communication)->withProperties([
            'event' => 'customer_communication_updated',
            'type' => $event->communication->type?->value ?? $event->communication->type,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer communication updated');
    }

    public function handleCustomerCommunicationDeleted(CustomerCommunicationDeleted $event): void
    {
        activity('customer_communications')->causedBy($event->actor)->performedOn($event->communication)->withProperties([
            'event' => 'customer_communication_archived',
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer communication archived');
    }

    public function handleCustomerCommunicationRestored(CustomerCommunicationRestored $event): void
    {
        activity('customer_communications')->causedBy($event->actor)->performedOn($event->communication)->withProperties([
            'event' => 'customer_communication_restored',
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ])->log('Customer communication restored');
    }
}
