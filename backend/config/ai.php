<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Driver
    |--------------------------------------------------------------------------
    |
    | Used when no DB provider is marked default. Drivers are resolved via the
    | registry below — never hardcode a vendor in application services.
    |
    */
    'default_driver' => env('AI_DEFAULT_DRIVER', 'null'),

    'timeout' => (int) env('AI_TIMEOUT', 30),

    'max_tokens' => (int) env('AI_MAX_TOKENS', 2048),

    'daily_token_limit' => (int) env('AI_DAILY_TOKEN_LIMIT', 250000),

    /*
    |--------------------------------------------------------------------------
    | Provider Credentials (optional env bootstrap)
    |--------------------------------------------------------------------------
    |
    | Used by seeders / ai:use-gemini. Runtime chat still reads encrypted
    | credentials from ai_providers — never hardcode vendors in services.
    |
    */
    'providers' => [
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'default_model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'default_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Driver Registry
    |--------------------------------------------------------------------------
    |
    | Map driver keys to concrete provider classes implementing AiProviderInterface.
    | Add future custom AI drivers here without changing domain services.
    |
    */
    'drivers' => [
        'openai' => \App\Domains\Ai\Providers\OpenAiProvider::class,
        'azure_openai' => \App\Domains\Ai\Providers\AzureOpenAiProvider::class,
        'gemini' => \App\Domains\Ai\Providers\GeminiProvider::class,
        'claude' => \App\Domains\Ai\Providers\ClaudeProvider::class,
        'null' => \App\Domains\Ai\Providers\NullAiProvider::class,
        'custom' => \App\Domains\Ai\Providers\CustomHttpAiProvider::class,
    ],

    'features' => [
        'suggestions' => true,
        'auto_categorization' => true,
        'smart_ticket_routing' => true,
        'content_suggestions' => true,
        'auto_translation' => true,
        'document_summarization' => true,
        'ai_search' => true,
        'knowledge_assistant' => true,
        'chat_assistant' => true,
    ],
];
