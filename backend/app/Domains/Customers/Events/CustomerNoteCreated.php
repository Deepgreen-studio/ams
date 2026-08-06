<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\CustomerNote;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerNoteCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CustomerNote $note,
        public readonly User $actor
    ) {}
}
