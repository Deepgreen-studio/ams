<?php

namespace App\Domains\Monitoring\Enums;

enum MonitoringLogCategory: string
{
    case Health = 'health';
    case Api = 'api';
    case Webhook = 'webhook';
    case Queue = 'queue';
    case Job = 'job';
    case Integration = 'integration';
    case Server = 'server';
    case Incident = 'incident';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
