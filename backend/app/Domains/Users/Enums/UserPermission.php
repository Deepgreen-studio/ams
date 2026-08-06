<?php

namespace App\Domains\Users\Enums;

/**
 * Canonical user-management permission names.
 * Wired to Spatie Permission; never hardcode role names for access control.
 */
final class UserPermission
{
    public const VIEW = 'users.view';

    public const CREATE = 'users.create';

    public const UPDATE = 'users.update';

    public const DELETE = 'users.delete';

    public const FORCE_DELETE = 'users.force-delete';

    public const RESTORE = 'users.restore';

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
            self::FORCE_DELETE,
            self::RESTORE,
        ];
    }
}
