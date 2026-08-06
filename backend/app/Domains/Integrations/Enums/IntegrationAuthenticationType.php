<?php

namespace App\Domains\Integrations\Enums;

enum IntegrationAuthenticationType: string
{
    case ApiKey = 'api_key';
    case BearerToken = 'bearer_token';
    case BasicAuth = 'basic_auth';
    case Jwt = 'jwt';
    case OAuth2 = 'oauth2';

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
            self::ApiKey => 'API Key',
            self::BearerToken => 'Bearer Token',
            self::BasicAuth => 'Basic Auth',
            self::Jwt => 'JWT',
            self::OAuth2 => 'OAuth2',
        };
    }
}
