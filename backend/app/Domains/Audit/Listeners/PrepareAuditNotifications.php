<?php

namespace App\Domains\Audit\Listeners;

use App\Domains\Audit\Events\ActivityLogged;
use App\Domains\Audit\Events\ApiLogged;
use App\Domains\Audit\Events\AuditCreated;
use App\Domains\Audit\Events\ErrorCaptured;
use App\Domains\Audit\Events\SystemEventCreated;

/**
 * Architecture stub for future notification / SIEM integrations.
 */
class PrepareAuditNotifications
{
    public function handleActivityLogged(ActivityLogged $event): void {}

    public function handleAuditCreated(AuditCreated $event): void {}

    public function handleApiLogged(ApiLogged $event): void {}

    public function handleSystemEventCreated(SystemEventCreated $event): void {}

    public function handleErrorCaptured(ErrorCaptured $event): void {}
}
