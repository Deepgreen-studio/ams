<?php

namespace App\Domains\Content\Enums;

enum ContentBodyFormat: string
{
    case Rich = 'rich';
    case Markdown = 'markdown';
    case Html = 'html';

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
            self::Rich => 'Rich Text',
            self::Markdown => 'Markdown',
            self::Html => 'HTML',
        };
    }
}
