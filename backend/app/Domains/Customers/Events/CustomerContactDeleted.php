<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\CustomerContact;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerContactDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CustomerContact $contact,
        public readonly User $actor
    ) {}
}
