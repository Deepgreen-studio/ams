<?php

namespace App\Domains\Support\Enums;

final class SupportPermission
{
    public const VIEW = 'support.view';

    public const CREATE = 'support.create';

    public const UPDATE = 'support.update';

    public const DELETE = 'support.delete';

    public const MANAGE = 'support.manage';

    public const TICKETS = 'support.tickets';

    public const BOARD = 'support.board';

    public const QUEUE = 'support.queue';

    public const ASSIGNMENT = 'support.assignment';

    public const SLA = 'support.sla';

    public const KNOWLEDGE = 'support.knowledge';

    public const CANNED = 'support.canned';

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
            self::TICKETS,
            self::BOARD,
            self::QUEUE,
            self::ASSIGNMENT,
            self::SLA,
            self::KNOWLEDGE,
            self::CANNED,
        ];
    }
}
