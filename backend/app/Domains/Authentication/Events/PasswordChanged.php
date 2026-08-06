<?php

namespace App\Domains\Authentication\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PasswordChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly User $user) {}
}
