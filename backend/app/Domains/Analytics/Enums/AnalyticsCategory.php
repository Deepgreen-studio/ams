<?php

namespace App\Domains\Analytics\Enums;

enum AnalyticsCategory: string
{
    case Business = 'business';
    case Operational = 'operational';
    case Application = 'application';
    case Customer = 'customer';
    case Api = 'api';
    case System = 'system';
    case Security = 'security';
    case Executive = 'executive';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Business => 'Business Analytics',
            self::Operational => 'Operational Analytics',
            self::Application => 'Application Analytics',
            self::Customer => 'Customer Analytics',
            self::Api => 'API Analytics',
            self::System => 'System Analytics',
            self::Security => 'Security Analytics',
            self::Executive => 'Executive Analytics',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Business => 'Revenue, growth, and portfolio performance across companies and products.',
            self::Operational => 'Delivery, automation, workflows, and day-to-day platform operations.',
            self::Application => 'App usage, sessions, installs, retention, and release health.',
            self::Customer => 'Customer lifecycle, subscriptions, engagement, and support signals.',
            self::Api => 'API traffic, latency, error rates, and integration throughput.',
            self::System => 'Infrastructure health, queues, jobs, and platform reliability.',
            self::Security => 'Logins, permissions, GDPR actions, threat signals, and API key activity.',
            self::Executive => 'CEO and leadership scorecards, KPI boards, trends, and forecasts.',
        };
    }
}
