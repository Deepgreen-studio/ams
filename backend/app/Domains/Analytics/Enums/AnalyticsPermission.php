<?php

namespace App\Domains\Analytics\Enums;

final class AnalyticsPermission
{
    public const VIEW = 'analytics.view';

    public const CREATE = 'analytics.create';

    public const UPDATE = 'analytics.update';

    public const DELETE = 'analytics.delete';

    public const EXPORT = 'analytics.export';

    public const MANAGE = 'analytics.manage';

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
            self::EXPORT,
            self::MANAGE,
        ];
    }
}
