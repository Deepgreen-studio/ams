<?php

namespace App\Domains\Roles\Events;

use App\Domains\Roles\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Role $role, public readonly User $actor) {}
}
