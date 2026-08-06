<?php

namespace App\Domains\Ai\Enums;

enum AiProviderDriver: string
{
    case OpenAi = 'openai';
    case AzureOpenAi = 'azure_openai';
    case Gemini = 'gemini';
    case Claude = 'claude';
    case Null = 'null';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::OpenAi => 'OpenAI',
            self::AzureOpenAi => 'Azure OpenAI',
            self::Gemini => 'Google Gemini',
            self::Claude => 'Anthropic Claude',
            self::Null => 'Null (Local Stub)',
            self::Custom => 'Custom AI',
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
