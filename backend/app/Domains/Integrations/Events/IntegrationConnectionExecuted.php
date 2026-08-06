<?php

namespace App\Domains\Integrations\Events;

use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\IntegrationConnectionLog;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntegrationConnectionExecuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Integration $integration,
        public readonly IntegrationConnectionLog $log,
        public readonly User $actor,
    ) {}
}
