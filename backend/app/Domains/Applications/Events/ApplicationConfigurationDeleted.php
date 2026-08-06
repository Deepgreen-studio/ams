<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationConfiguration;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationConfigurationDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationConfiguration $configuration,
        public readonly User $actor
    ) {}
}
