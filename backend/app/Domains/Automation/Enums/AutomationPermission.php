<?php

namespace App\Domains\Automation\Enums;

final class AutomationPermission
{
    public const VIEW = 'automation.view';

    public const CREATE = 'automation.create';

    public const UPDATE = 'automation.update';

    public const DELETE = 'automation.delete';

    public const MANAGE = 'automation.manage';

    public const RULES = 'automation.rules';

    public const HISTORY = 'automation.history';

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
            self::RULES,
            self::HISTORY,
        ];
    }
}
