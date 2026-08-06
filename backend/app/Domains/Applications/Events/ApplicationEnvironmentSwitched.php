<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationEnvironmentSwitched
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationEnvironment $environment,
        public readonly User $actor
    ) {}
}
