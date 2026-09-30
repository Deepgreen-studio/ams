<?php

namespace App\Domains\Applications\Enums;

final class ApplicationPermission
{
    public const VIEW = 'applications.view';

    public const CREATE = 'applications.create';

    public const UPDATE = 'applications.update';

    public const DELETE = 'applications.delete';

    public const RESTORE = 'applications.restore';

    public const VIEW_TRASH = 'applications.view-trash';

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
            self::RESTORE,
            self::VIEW_TRASH,
        ];
    }
}
