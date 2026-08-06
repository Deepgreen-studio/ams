<?php

namespace App\Domains\Content\Events;

use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentWorkflowHistory;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentWorkflowTransitioned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Content $content,
        public readonly User $actor,
        public readonly ContentWorkflowHistory $history
    ) {}
}
