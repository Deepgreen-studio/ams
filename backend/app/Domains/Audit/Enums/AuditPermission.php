<?php

namespace App\Domains\Audit\Enums;

final class AuditPermission
{
    public const VIEW = 'audit.view';

    public const EXPORT = 'audit.export';

    public const MANAGE = 'audit.manage';

    public const TRAIL = 'audit.trail';

    public const LOGIN = 'audit.login';

    public const EVENTS = 'audit.events';

    public const API = 'audit.api';

    public const ERRORS = 'audit.errors';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::EXPORT,
            self::MANAGE,
            self::TRAIL,
            self::LOGIN,
            self::EVENTS,
            self::API,
            self::ERRORS,
        ];
    }
}
