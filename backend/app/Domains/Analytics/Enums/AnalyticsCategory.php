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
            self::Business => "Revenue, growth, and portfolio.\nCompanies, products, and trends.",
            self::Operational => "Delivery, automation, workflows.\nDay-to-day platform operations.",
            self::Application => "Usage, sessions, and retention.\nRelease health for applications.",
            self::Customer => "Lifecycle, plans, and engagement.\nSupport signals per customer.",
            self::Api => "Traffic, latency, and errors.\nIntegration throughput.",
            self::System => "Health, queues, and reliability.\nInfrastructure and uptime.",
            self::Security => "Logins, permissions, and threats.\nGDPR actions and API keys.",
            self::Executive => "Scorecards, KPIs, and forecasts.\nLeadership performance view.",
        };
    }
}
