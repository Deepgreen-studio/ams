<?php

namespace App\Domains\Support\Listeners;

use App\Domains\Support\Events\SupportTicketCreated;
use App\Domains\Support\Services\SupportComplianceRoutingService;

class RoutePersonalDataTicketToCompliance
{
    public function __construct(
        private readonly SupportComplianceRoutingService $routingService,
    ) {}

    public function handle(SupportTicketCreated $event): void
    {
        $this->routingService->routeIfNeeded(
            $event->ticket->loadMissing(['customer', 'company']),
            $event->actor,
            $event->ticket->involves_personal_data ? true : null,
        );
    }
}
