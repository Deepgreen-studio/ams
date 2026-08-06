<?php

namespace App\Domains\Notifications\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case InApp = 'in_app';
    case Push = 'push';
    case Sms = 'sms';
    case WhatsApp = 'whatsapp';
    case Slack = 'slack';
    case Teams = 'teams';
    case Webhook = 'webhook';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::InApp => 'In-App',
            self::Push => 'Push Notification',
            self::Sms => 'SMS',
            self::WhatsApp => 'WhatsApp',
            self::Slack => 'Slack',
            self::Teams => 'Microsoft Teams',
            self::Webhook => 'Webhook',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Email => 'Send notifications by email.',
            self::InApp => 'Show notifications inside the AMS notification center.',
            self::Push => 'Mobile push notifications (future).',
            self::Sms => 'SMS text messages (future).',
            self::WhatsApp => 'WhatsApp messages (future).',
            self::Slack => 'Slack workspace messages (future).',
            self::Teams => 'Microsoft Teams messages (future).',
            self::Webhook => 'Deliver payloads to configured webhook endpoints.',
        };
    }

    public function isImplemented(): bool
    {
        return match ($this) {
            self::Email, self::InApp => true,
            self::Push, self::Sms, self::WhatsApp, self::Slack, self::Teams, self::Webhook => false,
        };
    }

    public function defaultEnabled(): bool
    {
        return match ($this) {
            self::Email, self::InApp => true,
            default => false,
        };
    }

    public function laravelChannel(): ?string
    {
        return match ($this) {
            self::Email => 'mail',
            self::InApp => 'database',
            default => null,
        };
    }

    public function preferenceKey(): string
    {
        return match ($this) {
            self::Email => 'email_enabled',
            self::InApp => 'in_app_enabled',
            self::Push => 'push_enabled',
            self::Sms => 'sms_enabled',
            self::WhatsApp => 'whatsapp_enabled',
            self::Slack => 'slack_enabled',
            self::Teams => 'teams_enabled',
            self::Webhook => 'webhook_enabled',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
