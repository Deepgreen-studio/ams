<?php

namespace App\Domains\Content\Events;

use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentVersion;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentVersionRestored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Content $content,
        public readonly User $actor,
        public readonly ContentVersion $version
    ) {}
}
