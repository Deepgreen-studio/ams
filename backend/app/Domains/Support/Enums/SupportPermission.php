<?php

namespace App\Domains\Support\Enums;

final class SupportPermission
{
    public const VIEW = 'support.view';

    public const CREATE = 'support.create';

    public const UPDATE = 'support.update';

    public const DELETE = 'support.delete';

    public const MANAGE = 'support.manage';

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
