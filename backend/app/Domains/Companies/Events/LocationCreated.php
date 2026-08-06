<?php

namespace App\Domains\Companies\Events;

use App\Domains\Companies\Models\CompanyLocation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CompanyLocation $location, public readonly User $actor) {}
}
