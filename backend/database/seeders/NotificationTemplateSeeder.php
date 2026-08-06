<?php

namespace Database\Seeders;

use App\Domains\Notifications\Enums\NotificationChannel;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Domains\Notifications\Models\NotificationTemplateVersion;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $actorId = User::query()->orderBy('id')->value('id');

        foreach (NotificationEventKey::cases() as $event) {
            foreach ([NotificationChannel::Email, NotificationChannel::InApp] as $channel) {
                $template = NotificationTemplate::query()->updateOrCreate(
                    [
                        'event_key' => $event->value,
                        'channel' => $channel->value,
                        'locale' => 'en',
                        'name' => $event->label().' '.$channel->label().' (System)',
                    ],
                    [
                        'subject' => $channel === NotificationChannel::Email
                            ? $event->label().': {{ticket_number}}'
                            : null,
                        'body' => $channel === NotificationChannel::Email
                            ? '<p>{{recipient_name}},</p><p>'.$event->description().'</p><p>Ticket: <strong>{{ticket_number}}</strong> — {{subject}}</p>'
                            : $event->label().': {{ticket_number}} — {{subject}}',
                        'available_variables' => $event->defaultVariables(),
                        'is_active' => true,
                        'is_system' => true,
                        'workflow_status' => NotificationTemplateStatus::Published->value,
                        'current_version' => 1,
                        'published_at' => now(),
                        'created_by' => $actorId,
                        'updated_by' => $actorId,
                    ]
                );

                if (! NotificationTemplateVersion::query()
                    ->where('notification_template_id', $template->id)
                    ->where('version', 1)
                    ->exists()) {
                    NotificationTemplateVersion::query()->create([
                        'notification_template_id' => $template->id,
                        'version' => 1,
                        'status' => NotificationTemplateStatus::Published->value,
                        'name' => $template->name,
                        'channel' => $template->channel?->value ?? $channel->value,
                        'locale' => 'en',
                        'event_key' => $event->value,
                        'subject' => $template->subject,
                        'body' => $template->body,
                        'available_variables' => $template->available_variables,
                        'priority' => 'normal',
                        'snapshot' => [
                            'name' => $template->name,
                            'channel' => $channel->value,
                            'locale' => 'en',
                            'event_key' => $event->value,
                            'subject' => $template->subject,
                            'body' => $template->body,
                            'available_variables' => $template->available_variables,
                            'priority' => 'normal',
                            'workflow_status' => NotificationTemplateStatus::Published->value,
                        ],
                        'reason' => 'System seed',
                        'created_by' => $actorId,
                        'created_at' => now(),
                    ]);
                }
            }
        }
    }
}
