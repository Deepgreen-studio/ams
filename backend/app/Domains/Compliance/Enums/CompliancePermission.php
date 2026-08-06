<?php

namespace App\Domains\Compliance\Enums;

final class CompliancePermission
{
    public const VIEW = 'compliance.view';

    public const CREATE = 'compliance.create';

    public const UPDATE = 'compliance.update';

    public const DELETE = 'compliance.delete';

    public const MANAGE = 'compliance.manage';

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
        ];
    }
}
