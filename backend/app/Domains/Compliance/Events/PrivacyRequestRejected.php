<?php

namespace App\Domains\Compliance\Events;

use App\Domains\Compliance\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivacyRequestRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PrivacyRequest $privacyRequest,
        public readonly User $actor
    ) {}
}
