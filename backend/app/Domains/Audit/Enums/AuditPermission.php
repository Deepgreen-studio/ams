<?php

namespace App\Domains\Audit\Enums;

final class AuditPermission
{
    public const VIEW = 'audit.view';

    public const EXPORT = 'audit.export';

    public const MANAGE = 'audit.manage';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::EXPORT,
            self::MANAGE,
        ];
    }
}
