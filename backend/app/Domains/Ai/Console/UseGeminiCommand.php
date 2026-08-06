<?php

namespace App\Domains\Ai\Console;

use App\Domains\Ai\Enums\AiProviderDriver;
use App\Domains\Ai\Models\AiProvider;
use App\Domains\Ai\Repositories\AiProviderRepository;
use App\Domains\Ai\Services\AiProviderManager;
use App\Models\User;
use Illuminate\Console\Command;

class UseGeminiCommand extends Command
{
    protected $signature = 'ai:use-gemini
                            {--key= : Google AI Studio / Gemini API key}
                            {--model=gemini-flash-latest : Default chat model}
                            {--make-default=1 : Mark Gemini as the default provider}';

    protected $description = 'Quick-add Google Gemini as an AI provider and optionally set it as default';

    public function handle(AiProviderRepository $providers, AiProviderManager $manager): int
    {
        $apiKey = (string) ($this->option('key') ?: config('ai.providers.gemini.api_key') ?: env('GEMINI_API_KEY'));
        if ($apiKey === '') {
            $this->error('Gemini API key is required.');
            $this->line('Pass --key=YOUR_KEY or set GEMINI_API_KEY in .env');
            $this->line('Get a key: https://aistudio.google.com/apikey');

            return self::FAILURE;
        }

        $model = (string) $this->option('model');
        $makeDefault = filter_var($this->option('make-default'), FILTER_VALIDATE_BOOLEAN);
        $actor = User::query()->orderBy('id')->first();

        if ($makeDefault) {
            $providers->clearDefaults(null);
        }

        /** @var AiProvider $provider */
        $provider = AiProvider::query()->updateOrCreate(
            ['slug' => 'google-gemini'],
            [
                'name' => 'Google Gemini',
                'driver' => AiProviderDriver::Gemini->value,
                'status' => 'active',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'default_model' => $model,
                'embedding_model' => 'text-embedding-004',
                'authentication_type' => 'api_key',
                'credentials' => ['api_key' => $apiKey],
                'config' => ['source' => 'ai:use-gemini'],
                'timeout_seconds' => (int) config('ai.timeout', 30),
                'retry_attempts' => 2,
                'is_default' => $makeDefault,
                'is_enabled' => true,
                'updated_by' => $actor?->id,
                'created_by' => $actor?->id,
            ]
        );

        $this->info("Google Gemini provider ready: {$provider->uuid}");
        $this->line("Model: {$provider->default_model}");
        $this->line('Default: '.($provider->is_default ? 'yes' : 'no'));

        try {
            $result = $manager->forProvider($provider->fresh())->testConnection();
            $providers->update($provider->id, [
                'health_status' => $result->healthy ? 'healthy' : 'unhealthy',
                'last_health_check_at' => now(),
                'status' => $result->healthy ? 'active' : $provider->status,
            ]);
            if ($result->healthy) {
                $this->info('Connection test: OK — '.$result->message);
            } else {
                $this->warn('Connection test failed: '.$result->message);
            }
        } catch (\Throwable $e) {
            $this->warn('Connection test error: '.$e->getMessage());
        }

        $this->line('Open AI Conversations → New chat (or pick Google Gemini in the provider dropdown).');

        return self::SUCCESS;
    }
}
