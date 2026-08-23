<?php

$parseOrigins = static function (?string $value): array {
    return array_values(array_unique(array_filter(array_map(
        static function (string $origin): string {
            $origin = trim($origin, " \n\r\t\v\0/");

            if ($origin === '') {
                return '';
            }

            $parts = parse_url($origin);

            if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
                return $origin;
            }

            $port = isset($parts['port']) ? ':' . $parts['port'] : '';

            return $parts['scheme'] . '://' . $parts['host'] . $port;
        },
        explode(',', (string) $value)
    ))));
};

$frontendOrigin = $parseOrigins((string) env('FRONTEND_URL', 'http://localhost:5173'));
$configuredOrigins = $parseOrigins((string) env(
    'CORS_ALLOWED_ORIGINS',
    'http://localhost:5173,http://localhost:5174,http://localhost:5175,http://127.0.0.1:5173,http://127.0.0.1:5174,http://127.0.0.1:5175'
));

$originPatterns = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env(
        'CORS_ALLOWED_ORIGIN_PATTERNS',
        '#^https://([a-z0-9-]+\.)?eh\.studio$#'
    ))
)));

$localOriginPattern = '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#';

if (env('APP_ENV') !== 'production' && ! in_array($localOriginPattern, $originPatterns, true)) {
    $originPatterns[] = $localOriginPattern;
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cross-origin resource sharing for the AMS API. FRONTEND_URL is
    | always merged so a stale CORS_ALLOWED_ORIGINS list cannot block the SPA.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_merge(
        $configuredOrigins,
        $frontendOrigin
    ))),

    'allowed_origins_patterns' => $originPatterns,

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 0),

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),

];
