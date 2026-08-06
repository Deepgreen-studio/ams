<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationCrashReport;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationCrashReported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationCrashReport $crash,
        public readonly ?User $actor,
        public readonly bool $fromIngest = false
    ) {}
}
