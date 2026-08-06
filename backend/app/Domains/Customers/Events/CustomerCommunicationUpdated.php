<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\CustomerCommunication;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerCommunicationUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CustomerCommunication $communication,
        public readonly User $actor
    ) {}
}
