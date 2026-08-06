<?php

return [
    'connection' => env('QUEUE_CONNECTION', 'database'),

    /*
    | Priority-aware worker order (high first).
    | php artisan queue:work --queue=high,imports,exports,webhooks,syncs,notifications,default,low
    */
    'worker_queues' => [
        'high',
        'imports',
        'exports',
        'webhooks',
        'syncs',
        'notifications',
        'default',
        'low',
    ],

    'priorities' => [
        'high' => 'high',
        'normal' => 'default',
        'low' => 'low',
    ],

    'types' => [
        'import' => ['queue' => 'imports', 'priority' => 'high'],
        'export' => ['queue' => 'exports', 'priority' => 'normal'],
        'webhook' => ['queue' => 'webhooks', 'priority' => 'high'],
        'sync' => ['queue' => 'syncs', 'priority' => 'normal'],
        'notification' => ['queue' => 'notifications', 'priority' => 'low'],
    ],

    'defaults' => [
        'tries' => (int) env('QUEUE_DEFAULT_TRIES', 3),
        'timeout' => (int) env('QUEUE_DEFAULT_TIMEOUT', 90),
        'backoff' => [10, 30, 60],
    ],
];
