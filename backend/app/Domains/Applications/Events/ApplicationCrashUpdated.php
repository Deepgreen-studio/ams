<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationCrashReport;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationCrashUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationCrashReport $crash,
        public readonly User $actor
    ) {}
}
