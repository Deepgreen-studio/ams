<?php

namespace App\Domains\Compliance\Enums;

enum RiskActionType: string
{
    case Mitigation = 'mitigation';
    case Review = 'review';
    case Approval = 'approval';
    case StatusChange = 'status_change';
    case Assessment = 'assessment';
    case Other = 'other';

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
            self::Mitigation => 'Mitigation',
            self::Review => 'Review',
            self::Approval => 'Approval',
            self::StatusChange => 'Status Change',
            self::Assessment => 'Assessment',
            self::Other => 'Other',
        };
    }
}
