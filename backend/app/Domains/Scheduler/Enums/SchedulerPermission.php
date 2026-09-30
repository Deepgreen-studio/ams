<?php

namespace App\Domains\Scheduler\Enums;

final class SchedulerPermission
{
    public const VIEW = 'scheduler.view';

    public const CREATE = 'scheduler.create';

    public const UPDATE = 'scheduler.update';

    public const DELETE = 'scheduler.delete';

    public const MANAGE = 'scheduler.manage';

    public const RETRY = 'scheduler.retry';

    public const JOBS = 'scheduler.jobs';

    public const HISTORY = 'scheduler.history';

    public const RUNNING = 'scheduler.running';

    public const FAILED = 'scheduler.failed';

    public const LOGS = 'scheduler.logs';

    public const STATISTICS = 'scheduler.statistics';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::MANAGE,
            self::RETRY,
            self::JOBS,
            self::HISTORY,
            self::RUNNING,
            self::FAILED,
            self::LOGS,
            self::STATISTICS,
        ];
    }
}
