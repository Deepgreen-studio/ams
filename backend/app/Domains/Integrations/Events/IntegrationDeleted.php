<?php

namespace App\Domains\Integrations\Events;

use App\Domains\Integrations\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntegrationDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Integration $integration, public readonly User $actor) {}
}
