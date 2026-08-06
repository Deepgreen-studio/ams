<?php

namespace App\Domains\Integrations\Enums;

enum MappingTransformType: string
{
    case None = 'none';
    case Trim = 'trim';
    case Uppercase = 'uppercase';
    case Lowercase = 'lowercase';
    case TitleCase = 'title_case';
    case CastString = 'cast_string';
    case CastInt = 'cast_int';
    case CastFloat = 'cast_float';
    case CastBool = 'cast_bool';
    case DateFormat = 'date_format';
    case Replace = 'replace';
    case Prefix = 'prefix';
    case Suffix = 'suffix';
    case SplitFirst = 'split_first';
    case SplitLast = 'split_last';
    case Lookup = 'lookup';
    case Template = 'template';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
