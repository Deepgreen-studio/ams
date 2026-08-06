<?php

namespace App\Domains\Integrations\Events;

use App\Domains\Integrations\Models\SyncConfig;
use App\Domains\Integrations\Models\SyncRun;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncRunStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SyncConfig $config,
        public readonly SyncRun $run,
        public readonly ?User $actor = null,
    ) {}
}
