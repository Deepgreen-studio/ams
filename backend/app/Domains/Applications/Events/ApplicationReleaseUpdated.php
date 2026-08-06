<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationRelease;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationReleaseUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationRelease $release,
        public readonly User $actor
    ) {}
}
