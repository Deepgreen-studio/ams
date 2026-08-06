<?php

namespace App\Domains\Settings\Events;

use App\Domains\Settings\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly MediaFile $media,
        public readonly User $actor
    ) {}
}
