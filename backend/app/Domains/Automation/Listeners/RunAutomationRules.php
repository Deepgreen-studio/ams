<?php

namespace App\Domains\Automation\Listeners;

use App\Domains\Applications\Events\ApplicationCreated;
use App\Domains\Applications\Events\ApplicationReleaseDeployed;
use App\Domains\Automation\Enums\AutomationEventKey;
use App\Domains\Automation\Services\AutomationEngineService;
use App\Domains\Customers\Events\CustomerCreated;
use App\Domains\Support\Events\SupportTicketAssigned;
use App\Domains\Support\Events\SupportTicketClosed;
use App\Domains\Support\Events\SupportTicketCreated;
use App\Models\User;

class RunAutomationRules
{
    public function __construct(
        private readonly AutomationEngineService $engineService,
    ) {}

    public function handleSupportTicketCreated(SupportTicketCreated $event): void
    {
        $ticket = $event->ticket;
        $this->engineService->handleEvent(AutomationEventKey::TicketCreated->value, [
            'ticket_uuid' => $ticket->uuid,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority?->value ?? $ticket->priority,
            'status' => $ticket->status?->value ?? $ticket->status,
            'category' => $ticket->category?->value ?? $ticket->category,
            'company_id' => $ticket->company_id,
            'customer_id' => $ticket->customer_id,
            'customer_uuid' => $ticket->customer?->uuid,
            'assigned_to' => $ticket->assigned_to,
            'assignee_id' => $ticket->assigned_to,
            'actor_id' => $event->actor->id,
            'actor_name' => $event->actor->full_name ?? $event->actor->name,
        ], $event->actor);
    }

    public function handleSupportTicketAssigned(SupportTicketAssigned $event): void
    {
        $ticket = $event->ticket;
        $this->engineService->handleEvent(AutomationEventKey::TicketAssigned->value, [
            'ticket_uuid' => $ticket->uuid,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority?->value ?? $ticket->priority,
            'status' => $ticket->status?->value ?? $ticket->status,
            'company_id' => $ticket->company_id,
            'customer_id' => $ticket->customer_id,
            'customer_uuid' => $ticket->customer?->uuid,
            'assigned_to' => $ticket->assigned_to,
            'assignee_id' => $ticket->assigned_to,
            'actor_id' => $event->actor->id,
        ], $event->actor);
    }

    public function handleSupportTicketClosed(SupportTicketClosed $event): void
    {
        $ticket = $event->ticket;
        $this->engineService->handleEvent(AutomationEventKey::TicketClosed->value, [
            'ticket_uuid' => $ticket->uuid,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority?->value ?? $ticket->priority,
            'status' => $ticket->status?->value ?? $ticket->status,
            'company_id' => $ticket->company_id,
            'customer_id' => $ticket->customer_id,
            'customer_uuid' => $ticket->customer?->uuid,
            'actor_id' => $event->actor->id,
        ], $event->actor);
    }

    public function handleCustomerCreated(CustomerCreated $event): void
    {
        $customer = $event->customer;
        $this->engineService->handleEvent(AutomationEventKey::CustomerCreated->value, [
            'customer_id' => $customer->id,
            'customer_uuid' => $customer->uuid,
            'email' => $customer->email,
            'customer_type' => $customer->customer_type?->value ?? $customer->customer_type,
            'status' => $customer->status?->value ?? $customer->status,
            'company_id' => $customer->company_id,
            'user_id' => User::query()->where('customer_id', $customer->id)->value('id'),
            'actor_id' => $event->actor->id,
        ], $event->actor);
    }

    public function handleApplicationCreated(ApplicationCreated $event): void
    {
        $application = $event->application;
        $this->engineService->handleEvent(AutomationEventKey::ApplicationCreated->value, [
            'application_id' => $application->id,
            'application_uuid' => $application->uuid,
            'name' => $application->name,
            'platform' => $application->platform?->value ?? $application->platform,
            'status' => $application->status?->value ?? $application->status,
            'company_id' => $application->company_id,
            'actor_id' => $event->actor->id,
        ], $event->actor);
    }

    public function handleApplicationReleaseDeployed(ApplicationReleaseDeployed $event): void
    {
        $release = $event->release;
        $this->engineService->handleEvent(AutomationEventKey::ApplicationReleaseDeployed->value, [
            'release_uuid' => $release->uuid,
            'version' => $release->version_label ?? $release->version?->version ?? null,
            'platform' => $release->platform?->value ?? $release->platform,
            'application_id' => $release->application_id,
            'company_id' => $release->application?->company_id ?? null,
            'actor_id' => $event->actor->id,
        ], $event->actor);
    }
}
