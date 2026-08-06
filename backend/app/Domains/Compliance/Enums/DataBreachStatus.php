<?php

namespace App\Domains\Compliance\Enums;

enum DataBreachStatus: string
{
    case Reported = 'reported';
    case Assessing = 'assessing';
    case Contained = 'contained';
    case Recovering = 'recovering';
    case Notifying = 'notifying';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

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
            self::Reported->value,
            self::Assessing->value,
            self::Contained->value,
            self::Recovering->value,
            self::Notifying->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function terminalValues(): array
    {
        return [
            self::Closed->value,
            self::Cancelled->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reported',
            self::Assessing => 'Assessing',
            self::Contained => 'Contained',
            self::Recovering => 'Recovering',
            self::Notifying => 'Notifying',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this->value, self::terminalValues(), true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Reported => [
                self::Assessing,
                self::Contained,
                self::Cancelled,
            ],
            self::Assessing => [
                self::Contained,
                self::Recovering,
                self::Notifying,
                self::Cancelled,
            ],
            self::Contained => [
                self::Recovering,
                self::Notifying,
                self::Closed,
                self::Cancelled,
            ],
            self::Recovering => [
                self::Notifying,
                self::Closed,
                self::Cancelled,
            ],
            self::Notifying => [
                self::Closed,
                self::Recovering,
                self::Cancelled,
            ],
            self::Closed => [],
            self::Cancelled => [
                self::Reported,
                self::Assessing,
            ],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
