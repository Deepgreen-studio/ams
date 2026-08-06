<?php

namespace App\Domains\Compliance\Enums;

enum DataBreachSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

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
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public static function fromRiskScore(int $score): self
    {
        return match (true) {
            $score >= 17 => self::Critical,
            $score >= 10 => self::High,
            $score >= 5 => self::Medium,
            default => self::Low,
        };
    }
}
