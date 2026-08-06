<?php

namespace App\Domains\Monitoring\Enums;

enum ServiceType: string
{
    case Application = 'application';
    case Api = 'api';
    case Webhook = 'webhook';
    case Queue = 'queue';
    case Job = 'job';
    case Integration = 'integration';
    case Server = 'server';
    case Database = 'database';
    case Cache = 'cache';
    case Scheduler = 'scheduler';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
