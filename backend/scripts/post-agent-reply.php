<?php

/**
 * Post a Public agent reply on the latest EasyCare SMS ticket and push to EasyCare.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Services\SupportTicketConversationService;
use App\Models\User;

$ticket = SupportTicket::query()
    ->where('ticket_number', 'SUP-20260807-00009')
    ->first()
    ?? SupportTicket::query()->latest('id')->first();

if (! $ticket) {
    fwrite(STDERR, "No support ticket found.\n");
    exit(1);
}

$actor = User::query()->orderBy('id')->first();
if (! $actor) {
    fwrite(STDERR, "No AMS user found to act as agent.\n");
    exit(1);
}

echo "Ticket {$ticket->ticket_number} uuid={$ticket->uuid}\n";
echo "Actor {$actor->id} {$actor->email}\n";

/** @var SupportTicketConversationService $svc */
$svc = app(SupportTicketConversationService::class);
$message = $svc->createMessage($ticket->uuid, [
    'body' => '<p>Hello from AMS — we received your SMS and will help shortly.</p>',
    'visibility' => 'public',
    'author_type' => 'agent',
], $actor);

echo "Created message {$message->uuid}\n";
echo "Check EasyCare dashboard for AMS SMS reply under the conversation.\n";
