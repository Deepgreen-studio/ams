<?php

namespace App\Domains\Compliance\Enums;

enum RiskRegisterStatus: string
{
    case Identified = 'identified';
    case Assessing = 'assessing';
    case Mitigating = 'mitigating';
    case Monitoring = 'monitoring';
    case Accepted = 'accepted';
    case Closed = 'closed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [
            self::Identified->value,
            self::Assessing->value,
            self::Mitigating->value,
            self::Monitoring->value,
            self::Accepted->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Identified => 'Identified',
            self::Assessing => 'Assessing',
            self::Mitigating => 'Mitigating',
            self::Monitoring => 'Monitoring',
            self::Accepted => 'Accepted',
            self::Closed => 'Closed',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Identified => [self::Assessing, self::Mitigating, self::Accepted, self::Closed],
            self::Assessing => [self::Mitigating, self::Monitoring, self::Accepted, self::Closed],
            self::Mitigating => [self::Monitoring, self::Accepted, self::Closed],
            self::Monitoring => [self::Mitigating, self::Accepted, self::Closed],
            self::Accepted => [self::Monitoring, self::Closed],
            self::Closed => [self::Identified, self::Monitoring],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
