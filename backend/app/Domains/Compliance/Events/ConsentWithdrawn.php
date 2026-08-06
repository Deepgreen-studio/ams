<?php

namespace App\Domains\Compliance\Events;

use App\Domains\Compliance\Models\UserConsent;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsentWithdrawn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserConsent $consent,
        public readonly User $actor
    ) {}
}
