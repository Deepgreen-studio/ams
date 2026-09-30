<?php

namespace App\Domains\Integrations\Enums;

final class WebhookPermission
{
    public const VIEW = 'webhooks.view';

    public const CREATE = 'webhooks.create';

    public const UPDATE = 'webhooks.update';

    public const DELETE = 'webhooks.delete';

    public const LOGS = 'webhooks.logs';

    public const EVENTS = 'webhooks.events';

    public const DOCS = 'webhooks.docs';

    public const TEST = 'webhooks.test';

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
            self::LOGS,
            self::EVENTS,
            self::DOCS,
            self::TEST,
        ];
    }
}
