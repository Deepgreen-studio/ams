<?php

namespace Database\Seeders;

use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Ai\Enums\AiPromptStatus;
use App\Domains\Ai\Enums\AiProviderDriver;
use App\Domains\Ai\Models\AiPrompt;
use App\Domains\Ai\Models\AiProvider;
use App\Domains\Ai\Models\AiSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->orderBy('id')->first();

        AiProvider::query()->firstOrCreate(
            ['slug' => 'local-null'],
            [
                'name' => 'Local Null Provider',
                'driver' => AiProviderDriver::Null->value,
                'status' => 'active',
                'default_model' => 'null-model',
                'embedding_model' => 'null-embed',
                'authentication_type' => 'none',
                'credentials' => [],
                'config' => ['stub' => true],
                'health_status' => 'healthy',
                'last_health_check_at' => now(),
                'timeout_seconds' => 30,
                'retry_attempts' => 0,
                'is_default' => blank(config('ai.providers.gemini.api_key')),
                'is_enabled' => true,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );

        $geminiKey = (string) config('ai.providers.gemini.api_key');
        if ($geminiKey !== '') {
            AiProvider::query()->where('is_default', true)->update(['is_default' => false]);

            AiProvider::query()->updateOrCreate(
                ['slug' => 'google-gemini'],
                [
                    'name' => 'Google Gemini',
                    'driver' => AiProviderDriver::Gemini->value,
                    'status' => 'active',
                    'base_url' => (string) config('ai.providers.gemini.base_url'),
                    'default_model' => (string) config('ai.providers.gemini.default_model', 'gemini-2.0-flash'),
                    'embedding_model' => 'text-embedding-004',
                    'authentication_type' => 'api_key',
                    'credentials' => ['api_key' => $geminiKey],
                    'config' => ['source' => 'AiSeeder'],
                    'timeout_seconds' => (int) config('ai.timeout', 30),
                    'retry_attempts' => 2,
                    'is_default' => true,
                    'is_enabled' => true,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ]
            );
        }

        $prompts = [
            [
                'key' => 'chat_assistant_default',
                'name' => 'Chat Assistant Default',
                'feature' => AiFeature::ChatAssistant->value,
                'system_prompt' => 'You are the AMS enterprise AI assistant. Be concise, accurate, and operationally helpful.',
                'user_template' => '{{message}}',
            ],
            [
                'key' => 'ticket_routing_default',
                'name' => 'Smart Ticket Routing',
                'feature' => AiFeature::SmartTicketRouting->value,
                'system_prompt' => 'You route support tickets to the best team and suggest priority.',
                'user_template' => "Subject: {{subject}}\nDescription: {{description}}\nTeams: {{teams}}",
            ],
            [
                'key' => 'summarization_default',
                'name' => 'Document Summarization',
                'feature' => AiFeature::DocumentSummarization->value,
                'system_prompt' => 'Summarize documents for enterprise operators. Preserve key facts and action items.',
                'user_template' => '{{text}}',
            ],
            [
                'key' => 'translation_default',
                'name' => 'Auto Translation',
                'feature' => AiFeature::AutoTranslation->value,
                'system_prompt' => 'Translate content accurately while preserving meaning and tone.',
                'user_template' => '{{text}}',
            ],
            [
                'key' => 'knowledge_assistant_default',
                'name' => 'Knowledge Assistant',
                'feature' => AiFeature::KnowledgeAssistant->value,
                'system_prompt' => 'Answer AMS platform questions using clear enterprise guidance.',
                'user_template' => '{{question}}',
            ],
            [
                'key' => 'content_suggestions_default',
                'name' => 'Content Suggestions',
                'feature' => AiFeature::ContentSuggestions->value,
                'system_prompt' => 'Suggest SEO and editorial improvements for CMS content.',
                'user_template' => '{{text}}',
            ],
            [
                'key' => 'categorization_default',
                'name' => 'Auto Categorization',
                'feature' => AiFeature::AutoCategorization->value,
                'system_prompt' => 'Classify text into the provided labels with confidence.',
                'user_template' => '{{text}}',
            ],
            [
                'key' => 'suggestions_default',
                'name' => 'AI Suggestions',
                'feature' => AiFeature::Suggestions->value,
                'system_prompt' => 'Provide actionable operational suggestions.',
                'user_template' => '{{text}}',
            ],
            [
                'key' => 'ai_search_default',
                'name' => 'AI Search',
                'feature' => AiFeature::AiSearch->value,
                'system_prompt' => 'Rank and explain semantic search matches.',
                'user_template' => '{{query}}',
            ],
        ];

        foreach ($prompts as $prompt) {
            AiPrompt::query()->firstOrCreate(
                ['key' => $prompt['key'], 'company_id' => null],
                array_merge($prompt, [
                    'status' => AiPromptStatus::Published->value,
                    'version' => 1,
                    'temperature' => 0.2,
                    'max_tokens' => 1024,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ])
            );
        }

        AiSetting::query()->firstOrCreate(
            ['key' => 'features', 'company_id' => null],
            [
                'group' => 'features',
                'value' => config('ai.features', []),
            ]
        );

        AiSetting::query()->firstOrCreate(
            ['key' => 'defaults', 'company_id' => null],
            [
                'group' => 'general',
                'value' => [
                    'default_driver' => config('ai.default_driver'),
                    'timeout' => config('ai.timeout'),
                    'max_tokens' => config('ai.max_tokens'),
                    'daily_token_limit' => config('ai.daily_token_limit'),
                ],
            ]
        );
    }
}
