<?php

namespace App\Domains\Automation\Enums;

enum AutomationActionType: string
{
    case SendEmail = 'send_email';
    case SendNotification = 'send_notification';
    case SendPush = 'send_push';
    case CreateTask = 'create_task';
    case AssignAgent = 'assign_agent';
    case AssignRole = 'assign_role';
    case GenerateApiKey = 'generate_api_key';
    case NotifyCustomers = 'notify_customers';

    public function label(): string
    {
        return match ($this) {
            self::SendEmail => 'Send Email',
            self::SendNotification => 'Send In-App Notification',
            self::SendPush => 'Send Push Notification',
            self::CreateTask => 'Create Task',
            self::AssignAgent => 'Assign Agent',
            self::AssignRole => 'Assign Default Role',
            self::GenerateApiKey => 'Generate API Key',
            self::NotifyCustomers => 'Notify Customers',
        };
    }

    public function isImplemented(): bool
    {
        return match ($this) {
            self::SendPush => false,
            default => true,
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
