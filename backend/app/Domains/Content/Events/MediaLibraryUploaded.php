<?php

namespace App\Domains\Content\Events;

use App\Domains\Content\Models\MediaLibraryItem;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaLibraryUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MediaLibraryItem $media,
        public readonly User $actor
    ) {}
}
