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

    public const SETTINGS = 'ai.settings';

    public const PROMPTS = 'ai.prompts';

    public const CONVERSATIONS = 'ai.conversations';

    public const ANALYTICS = 'ai.analytics';

    public const LOGS = 'ai.logs';

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
            self::SETTINGS,
            self::PROMPTS,
            self::CONVERSATIONS,
            self::ANALYTICS,
            self::LOGS,
        ];
    }
}
