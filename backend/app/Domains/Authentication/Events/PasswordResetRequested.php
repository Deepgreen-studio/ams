<?php

namespace App\Domains\Authentication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PasswordResetRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly string $email) {}
}
