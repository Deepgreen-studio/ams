<?php

namespace App\Domains\Applications\Enums;

final class ApplicationPermission
{
    public const VIEW = 'applications.view';

    public const CREATE = 'applications.create';

    public const UPDATE = 'applications.update';

    public const DELETE = 'applications.delete';

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
        ];
    }
}
