<?php

namespace App\Domains\Integrations\Enums;

enum ConnectorCapability: string
{
    case Api = 'api';
    case OAuth = 'oauth';
    case Webhook = 'webhook';
    case ApiKey = 'api_key';
    case ScheduledSync = 'scheduled_sync';
    case QueueRetry = 'queue_retry';
    case Monitoring = 'monitoring';
    case Credentials = 'credentials';

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
            self::Api => 'API',
            self::OAuth => 'OAuth',
            self::Webhook => 'Webhooks',
            self::ApiKey => 'API key',
            self::ScheduledSync => 'Scheduled sync',
            self::QueueRetry => 'Queues and retries',
            self::Monitoring => 'Monitoring',
            self::Credentials => 'Credentials',
        };
    }
}
