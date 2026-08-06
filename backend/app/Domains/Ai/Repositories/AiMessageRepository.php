<?php

namespace App\Domains\Ai\Repositories;

use App\Domains\Ai\Models\AiMessage;
use App\Shared\Repositories\BaseRepository;

class AiMessageRepository extends BaseRepository
{
    public function __construct(AiMessage $model)
    {
        parent::__construct($model);
    }
}
