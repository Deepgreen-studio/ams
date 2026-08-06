<?php

namespace App\Domains\Scheduler\Enums;

enum ScheduledJobHandler: string
{
    case DailyReport = 'daily_report';
    case AnalyticsReport = 'analytics_report';
    case WeeklyBackup = 'weekly_backup';
    case MonthlyInvoice = 'monthly_invoice';
    case HealthCheck = 'health_check';
    case CustomerReminder = 'customer_reminder';
    case SubscriptionRenewal = 'subscription_renewal';
    case DeleteExpiredData = 'delete_expired_data';
    case CustomCommand = 'custom_command';

    public function label(): string
    {
        return match ($this) {
            self::DailyReport => 'Daily Report',
            self::AnalyticsReport => 'Analytics Report',
            self::WeeklyBackup => 'Weekly Backup',
            self::MonthlyInvoice => 'Monthly Invoice',
            self::HealthCheck => 'Health Check',
            self::CustomerReminder => 'Customer Reminder',
            self::SubscriptionRenewal => 'Subscription Renewal',
            self::DeleteExpiredData => 'Delete Expired Data',
            self::CustomCommand => 'Custom Artisan Command',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DailyReport => 'Generate and dispatch daily operational reports.',
            self::AnalyticsReport => 'Generate a configured enterprise analytics report definition.',
            self::WeeklyBackup => 'Run weekly platform backup checklist.',
            self::MonthlyInvoice => 'Prepare monthly invoice generation cycle.',
            self::HealthCheck => 'Capture platform health / monitoring snapshot.',
            self::CustomerReminder => 'Send pending customer reminder notifications.',
            self::SubscriptionRenewal => 'Process upcoming subscription renewals.',
            self::DeleteExpiredData => 'Purge expired soft-deleted / retention data.',
            self::CustomCommand => 'Execute a configured artisan command.',
        };
    }

    public function defaultCron(): ?string
    {
        return match ($this) {
            self::DailyReport => '0 6 * * *',
            self::AnalyticsReport => '0 7 * * *',
            self::WeeklyBackup => '0 2 * * 0',
            self::MonthlyInvoice => '0 3 1 * *',
            self::HealthCheck => '*/5 * * * *',
            self::CustomerReminder => '0 9 * * *',
            self::SubscriptionRenewal => '0 1 * * *',
            self::DeleteExpiredData => '30 3 * * *',
            self::CustomCommand => null,
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
