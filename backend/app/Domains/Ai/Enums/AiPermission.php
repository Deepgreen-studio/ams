<?php

namespace App\Domains\Ai\Enums;

final class AiPermission
{
    public const VIEW = 'ai.view';

    public const CREATE = 'ai.create';

    public const UPDATE = 'ai.update';

    public const DELETE = 'ai.delete';

    public const MANAGE = 'ai.manage';

    public const CHAT = 'ai.chat';

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
            self::CHAT,
        ];
    }
}
