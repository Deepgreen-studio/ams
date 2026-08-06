<?php

namespace App\Domains\Integrations\Enums;

enum IntegrationType: string
{
    case RestApi = 'rest_api';
    case GraphQl = 'graphql';
    case Webhook = 'webhook';
    case Sdk = 'sdk';
    case Ftp = 'ftp';
    case Database = 'database';

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
            self::RestApi => 'REST API',
            self::GraphQl => 'GraphQL',
            self::Webhook => 'Webhook',
            self::Sdk => 'SDK',
            self::Ftp => 'FTP',
            self::Database => 'Database',
        };
    }
}
