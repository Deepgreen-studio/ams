<?php

namespace App\Domains\Integrations\Events;

use App\Domains\Integrations\Models\DataMapping;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataMappingCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly DataMapping $mapping, public readonly User $actor) {}
}
