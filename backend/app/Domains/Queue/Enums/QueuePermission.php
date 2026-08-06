<?php

namespace App\Domains\Queue\Enums;

final class QueuePermission
{
    public const VIEW = 'queue.view';

    public const MANAGE = 'queue.manage';

    public const RETRY = 'queue.retry';
}
