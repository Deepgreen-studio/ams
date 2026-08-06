<?php

namespace App\Domains\Audit\Events;

use App\Domains\Audit\Models\ApiLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiLogged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ApiLog $apiLog) {}
}
