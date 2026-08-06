<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\CustomerDocument;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerDocumentDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CustomerDocument $document,
        public readonly User $actor
    ) {}
}
