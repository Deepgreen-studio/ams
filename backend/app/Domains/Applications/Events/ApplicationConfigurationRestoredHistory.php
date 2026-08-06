<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationConfiguration;
use App\Domains\Applications\Models\ApplicationConfigurationHistory;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationConfigurationRestoredHistory
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationConfiguration $configuration,
        public readonly User $actor,
        public readonly ApplicationConfigurationHistory $history
    ) {}
}
