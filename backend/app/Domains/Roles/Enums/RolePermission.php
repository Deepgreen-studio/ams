<?php

namespace App\Domains\Roles\Enums;

/**
 * Canonical roles-module permission names.
 * Never authorize by hardcoded role names in controllers.
 */
final class RolePermission
{
    public const VIEW = 'roles.view';

    public const CREATE = 'roles.create';

    public const UPDATE = 'roles.update';

    public const DELETE = 'roles.delete';

    public const RESTORE = 'roles.restore';

    public const FORCE_DELETE = 'roles.force-delete';

    public const ASSIGN = 'roles.assign';

    public const ASSIGN_USERS = 'users.assign-roles';

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
            self::FORCE_DELETE,
            self::ASSIGN,
            self::ASSIGN_USERS,
        ];
    }
}
