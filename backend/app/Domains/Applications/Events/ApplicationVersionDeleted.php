<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationVersion;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationVersionDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationVersion $version,
        public readonly User $actor
    ) {}
}
