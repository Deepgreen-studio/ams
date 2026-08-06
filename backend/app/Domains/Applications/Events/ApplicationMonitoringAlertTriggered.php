<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationMonitoringAlert;
use App\Domains\Applications\Models\ApplicationMonitoringAlertEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationMonitoringAlertTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationMonitoringAlertEvent $event,
        public readonly ApplicationMonitoringAlert $alert
    ) {}
}
