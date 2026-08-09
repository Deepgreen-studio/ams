<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = App\Domains\Integrations\Models\Webhook::query()
    ->whereIn('slug', ['easycare', 'easycare-replies'])
    ->get();

foreach ($rows as $row) {
    echo implode(' | ', [
        $row->slug,
        $row->direction?->value ?? (string) $row->direction,
        $row->status?->value ?? (string) $row->status,
        'url='.($row->url ?? '-'),
        'header='.$row->signature_header,
        'secret='.(is_string($row->secret) ? substr($row->secret, 0, 8).'…' : 'n/a'),
    ]).PHP_EOL;
}

echo 'APP_URL='.config('app.url').PHP_EOL;
echo 'count='.$rows->count().PHP_EOL;
