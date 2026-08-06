<?php

namespace App\Domains\Companies\Events;

use App\Domains\Companies\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepartmentUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Department $department, public readonly User $actor) {}
}
