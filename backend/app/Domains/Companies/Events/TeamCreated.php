<?php

namespace App\Domains\Companies\Events;

use App\Domains\Companies\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Team $team, public readonly User $actor) {}
}
