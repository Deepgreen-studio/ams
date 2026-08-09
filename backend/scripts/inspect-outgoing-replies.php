<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$logs = DB::table('webhook_delivery_logs')
    ->whereIn('id', [13, 14, 16, 17])
    ->orWhere(function ($q) {
        $q->where('direction', 'outgoing')
            ->whereIn('event_name', ['support.reply.sent', 'support.sms.sent']);
    })
    ->orderByDesc('id')
    ->limit(10)
    ->get();

foreach ($logs as $l) {
    echo "#{$l->id} {$l->direction} {$l->event_name} http={$l->response_status}\n";
    echo 'REQ: '.substr((string) $l->request_body, 0, 1000)."\n";
    echo 'RESP: '.substr((string) $l->response_body, 0, 500)."\n\n";
}
