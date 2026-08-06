<?php

namespace App\Domains\Integrations\Enums;

enum WebhookSignatureAlgorithm: string
{
    case HmacSha256 = 'hmac_sha256';
    case HmacSha1 = 'hmac_sha1';
    case None = 'none';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
