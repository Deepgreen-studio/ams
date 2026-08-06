<?php

namespace App\Domains\Settings\Enums;

enum SettingType: string
{
    case String = 'string';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case Json = 'json';
    case Url = 'url';
    case Email = 'email';
    case Encrypted = 'encrypted';
}
