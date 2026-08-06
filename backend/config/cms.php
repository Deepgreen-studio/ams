<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public CMS site URL (canonical / OG / sitemap)
    |--------------------------------------------------------------------------
    */
    'site_url' => rtrim((string) env('CMS_SITE_URL', env('FRONTEND_URL', env('APP_URL', 'http://localhost'))), '/'),

    'sitemap' => [
        'path_pattern' => env('CMS_CONTENT_PATH_PATTERN', '/content/{type}/{slug}'),
        'include_categories' => (bool) env('CMS_SITEMAP_INCLUDE_CATEGORIES', true),
        'include_tags' => (bool) env('CMS_SITEMAP_INCLUDE_TAGS', true),
        'changefreq' => env('CMS_SITEMAP_CHANGEFREQ', 'weekly'),
        'priority' => env('CMS_SITEMAP_PRIORITY', '0.7'),
    ],

    'robots' => [
        'allow' => ['/'],
        'disallow' => [
            '/admin',
            '/api',
            '/sanctum',
        ],
        'sitemap_url' => env('CMS_ROBOTS_SITEMAP_URL'),
    ],

    'api_key_prefix' => 'cms_',
];
