<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Models\SupportTicketMessage;

echo "=== AMS outgoing logs (latest 15) ===\n";
foreach (WebhookLog::query()->latest('id')->limit(15)->get() as $l) {
    $dir = $l->direction instanceof BackedEnum ? $l->direction->value : (string) $l->direction;
    $st = $l->status instanceof BackedEnum ? $l->status->value : (string) $l->status;
    echo "#{$l->id} {$dir} {$l->event_name} {$st} http={$l->response_status} url=".substr((string) $l->url, 0, 70)."\n";
}

$ticket = SupportTicket::query()->where('ticket_number', 'SUP-20260807-00009')->first()
    ?? SupportTicket::query()->latest('id')->first();

if (! $ticket) {
    echo "No tickets\n";
    exit(0);
}

$source = $ticket->source instanceof BackedEnum ? $ticket->source->value : (string) $ticket->source;
echo "=== Ticket {$ticket->ticket_number} {$ticket->uuid} source={$source} ===\n";
foreach (SupportTicketMessage::query()->where('support_ticket_id', $ticket->id)->orderBy('id')->get() as $m) {
    $vis = $m->visibility instanceof BackedEnum ? $m->visibility->value : (string) $m->visibility;
    $auth = $m->author_type instanceof BackedEnum ? $m->author_type->value : (string) $m->author_type;
    echo "msg {$m->id} {$vis}/{$auth}: ".substr(strip_tags((string) $m->body), 0, 60)."\n";
}

echo "=== outbound reply responses ===\n";
foreach (WebhookLog::query()
    ->whereIn('event_name', ['support.reply.sent', 'support.sms.sent'])
    ->latest('id')
    ->limit(6)
    ->get() as $l) {
    echo "#{$l->id} {$l->event_name} http={$l->response_status}\n";
    echo 'RESP: '.substr((string) $l->response_body, 0, 400)."\n";
    echo 'REQ data keys: ';
    $decoded = json_decode((string) $l->request_body, true);
    $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
    echo implode(',', array_keys($data))."\n\n";
}

echo "=== pending jobs ===\n";
echo 'jobs='.Illuminate\Support\Facades\DB::table('jobs')->count()."\n";
echo 'failed='.Illuminate\Support\Facades\DB::table('failed_jobs')->count()."\n";
