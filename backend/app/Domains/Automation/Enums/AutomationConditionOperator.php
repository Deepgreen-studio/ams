<?php

namespace App\Domains\Automation\Enums;

enum AutomationConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case In = 'in';
    case NotIn = 'not_in';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case IsSet = 'is_set';
    case IsEmpty = 'is_empty';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'Equals',
            self::NotEquals => 'Not Equals',
            self::Contains => 'Contains',
            self::In => 'In',
            self::NotIn => 'Not In',
            self::GreaterThan => 'Greater Than',
            self::LessThan => 'Less Than',
            self::IsSet => 'Is Set',
            self::IsEmpty => 'Is Empty',
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
