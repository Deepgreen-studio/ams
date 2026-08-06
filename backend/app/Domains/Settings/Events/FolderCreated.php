<?php

namespace App\Domains\Settings\Events;

use App\Domains\Settings\Models\FileFolder;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FolderCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly FileFolder $folder,
        public readonly User $actor
    ) {}
}
