<?php

namespace Database\Seeders;

use App\Domains\Integrations\Models\WebhookEvent;
use Illuminate\Database\Seeder;

class WebhookEventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            ['name' => 'webhook.test', 'label' => 'Webhook Test', 'source_module' => 'integrations', 'description' => 'Manual webhook testing event.'],
            ['name' => 'webhook.retry', 'label' => 'Webhook Retry', 'source_module' => 'integrations', 'description' => 'Retried webhook delivery event.'],
            ['name' => 'incoming.webhook', 'label' => 'Incoming Webhook', 'source_module' => 'integrations', 'description' => 'Generic incoming webhook payload received.'],
            ['name' => 'integration.created', 'label' => 'Integration Created', 'source_module' => 'integrations', 'description' => 'Fired when an integration is created.'],
            ['name' => 'integration.updated', 'label' => 'Integration Updated', 'source_module' => 'integrations', 'description' => 'Fired when an integration is updated.'],
            ['name' => 'integration.deleted', 'label' => 'Integration Deleted', 'source_module' => 'integrations', 'description' => 'Fired when an integration is deleted.'],
            ['name' => 'user.created', 'label' => 'User Created', 'source_module' => 'users', 'description' => 'Fired when a user is created.'],
            ['name' => 'user.updated', 'label' => 'User Updated', 'source_module' => 'users', 'description' => 'Fired when a user is updated.'],
            ['name' => 'company.created', 'label' => 'Company Created', 'source_module' => 'companies', 'description' => 'Fired when a company is created.'],
            ['name' => 'company.updated', 'label' => 'Company Updated', 'source_module' => 'companies', 'description' => 'Fired when a company is updated.'],
            ['name' => 'support.sms.received', 'label' => 'Support SMS Received', 'source_module' => 'support', 'description' => 'External app forwarded an inbound support SMS to AMS.'],
            ['name' => 'support.message.received', 'label' => 'Support Message Received', 'source_module' => 'support', 'description' => 'External app forwarded a support message (web/chat/email/etc.) to AMS.'],
            ['name' => 'support.ticket.created', 'label' => 'External Support Ticket Created', 'source_module' => 'support', 'description' => 'External app created a support ticket payload for AMS ingest.'],
            ['name' => 'support.reply.sent', 'label' => 'Support Reply Sent', 'source_module' => 'support', 'description' => 'Agent public reply ready to push to connected app / website.'],
            ['name' => 'support.sms.sent', 'label' => 'Support SMS Sent', 'source_module' => 'support', 'description' => 'Agent public SMS reply ready to send via connected app gateway.'],
        ];

        foreach ($events as $event) {
            WebhookEvent::query()->updateOrCreate(
                ['name' => $event['name']],
                [
                    'label' => $event['label'],
                    'description' => $event['description'],
                    'source_module' => $event['source_module'],
                    'is_system' => true,
                    'status' => 'active',
                ]
            );
        }
    }
}
