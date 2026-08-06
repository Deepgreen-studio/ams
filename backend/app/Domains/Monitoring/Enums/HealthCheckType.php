<?php

namespace App\Domains\Monitoring\Enums;

enum HealthCheckType: string
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

    public function label(): string
    {
        return match ($this) {
            self::Application => 'Application Health',
            self::Api => 'API Response',
            self::Webhook => 'Webhook Delivery',
            self::Queue => 'Queue Status',
            self::Job => 'Job Status',
            self::Integration => 'Integration Status',
            self::Server => 'Server Status',
            self::Database => 'Database',
            self::Cache => 'Cache',
            self::Scheduler => 'Scheduler',
        };
    }
}
