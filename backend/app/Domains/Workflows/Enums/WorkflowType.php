<?php

namespace App\Domains\Workflows\Enums;

enum WorkflowType: string
{
    case Approval = 'approval';
    case Business = 'business';
    case Sequential = 'sequential';
    case Parallel = 'parallel';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Approval => 'Approval Workflow',
            self::Business => 'Business Workflow',
            self::Sequential => 'Sequential Workflow',
            self::Parallel => 'Parallel Workflow',
            self::Custom => 'Custom Workflow',
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
