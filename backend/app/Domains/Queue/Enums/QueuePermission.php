<?php

namespace App\Domains\Queue\Enums;

final class QueuePermission
{
    public const VIEW = 'queue.view';

    public const MANAGE = 'queue.manage';

    public const RETRY = 'queue.retry';

    public const RUNNING = 'queue.running';

    public const FAILED = 'queue.failed';

    public const STATISTICS = 'queue.statistics';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::MANAGE,
            self::RETRY,
            self::RUNNING,
            self::FAILED,
            self::STATISTICS,
        ];
    }
}
