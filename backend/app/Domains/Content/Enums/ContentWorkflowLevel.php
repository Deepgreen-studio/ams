<?php

namespace App\Domains\Content\Enums;

enum ContentWorkflowLevel: string
{
    case Writer = 'writer';
    case Editor = 'editor';
    case Manager = 'manager';
    case Administrator = 'administrator';

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
            self::Writer => 'Content Writer',
            self::Editor => 'Editor',
            self::Manager => 'Manager',
            self::Administrator => 'Administrator',
        };
    }
}
