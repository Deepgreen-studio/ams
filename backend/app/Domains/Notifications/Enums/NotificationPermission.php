<?php

namespace App\Domains\Notifications\Enums;

final class NotificationPermission
{
    public const VIEW = 'notifications.view';

    public const CREATE = 'notifications.create';

    public const UPDATE = 'notifications.update';

    public const DELETE = 'notifications.delete';

    public const APPROVE = 'notifications.approve';

    public const PUBLISH = 'notifications.publish';

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
            self::APPROVE,
            self::PUBLISH,
        ];
    }
}
