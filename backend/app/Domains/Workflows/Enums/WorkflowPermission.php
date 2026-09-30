<?php

namespace App\Domains\Workflows\Enums;

final class WorkflowPermission
{
    public const VIEW = 'workflows.view';

    public const CREATE = 'workflows.create';

    public const UPDATE = 'workflows.update';

    public const DELETE = 'workflows.delete';

    public const MANAGE = 'workflows.manage';

    public const APPROVE = 'workflows.approve';

    public const DESIGNER = 'workflows.designer';

    public const MONITOR = 'workflows.monitor';

    public const HISTORY = 'workflows.history';

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
            self::APPROVE,
            self::DESIGNER,
            self::MONITOR,
            self::HISTORY,
        ];
    }
}
