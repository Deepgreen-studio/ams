<?php

namespace App\Domains\Ai\Providers;

use App\Domains\Ai\DTOs\AiChatRequest;
use App\Domains\Ai\DTOs\AiChatResult;
use App\Domains\Ai\DTOs\AiEmbeddingRequest;
use App\Domains\Ai\DTOs\AiEmbeddingResult;
use App\Domains\Ai\DTOs\AiHealthResult;
use App\Shared\Exceptions\ApiException;

class OpenAiProvider extends AbstractHttpAiProvider
{
    public function driver(): string
    {
        return 'openai';
    }

    public function testConnection(): AiHealthResult
    {
        $this->requireConfigured();
        $started = microtime(true);

        try {
            $response = $this->client()->get($this->endpoint('/models'));
            $latency = (int) ((microtime(true) - $started) * 1000);

            if (! $response->successful()) {
                return new AiHealthResult(false, 'OpenAI health check failed: '.$response->status(), [
                    'body' => $response->json(),
                ], $latency);
            }

            return new AiHealthResult(true, 'OpenAI connection successful.', [
                'models' => count($response->json('data') ?? []),
            ], $latency);
        } catch (\Throwable $exception) {
            return new AiHealthResult(false, $exception->getMessage(), [], (int) ((microtime(true) - $started) * 1000));
        }
    }

    public function chat(AiChatRequest $request): AiChatResult
    {
        $this->requireConfigured();
        $model = $this->resolveModel($request->model, 'gpt-4o-mini');
        $started = microtime(true);

        $payload = [
            'model' => $model,
            'messages' => $request->messages,
        ];
        if ($request->temperature !== null) {
            $payload['temperature'] = $request->temperature;
        }
        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        $response = $this->client()->post($this->endpoint('/chat/completions'), $payload);
        $latency = (int) ((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw $this->providerHttpError('OpenAI', $response, 'chat');
        }

        $json = $response->json();

        return new AiChatResult(
            content: (string) data_get($json, 'choices.0.message.content', ''),
            model: (string) data_get($json, 'model', $model),
            tokensIn: (int) data_get($json, 'usage.prompt_tokens', 0),
            tokensOut: (int) data_get($json, 'usage.completion_tokens', 0),
            finishReason: data_get($json, 'choices.0.finish_reason'),
            requestId: $response->header('x-request-id'),
            latencyMs: $latency,
            raw: is_array($json) ? $json : [],
        );
    }

    public function embed(AiEmbeddingRequest $request): AiEmbeddingResult
    {
        $this->requireConfigured();
        $model = $request->model ?: (string) ($this->provider?->embedding_model ?: 'text-embedding-3-small');
        $started = microtime(true);

        $response = $this->client()->post($this->endpoint('/embeddings'), [
            'model' => $model,
            'input' => $request->inputs,
        ]);
        $latency = (int) ((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw $this->providerHttpError('OpenAI', $response, 'embedding');
        }

        $json = $response->json();
        $embeddings = collect($json['data'] ?? [])->pluck('embedding')->map(fn ($item) => array_map('floatval', $item))->values()->all();

        return new AiEmbeddingResult(
            embeddings: $embeddings,
            model: $model,
            tokensIn: (int) data_get($json, 'usage.prompt_tokens', 0),
            latencyMs: $latency,
            raw: is_array($json) ? $json : [],
        );
    }

    protected function client()
    {
        $apiKey = (string) ($this->credentials()['api_key'] ?? '');
        if ($apiKey === '') {
            throw new ApiException('OpenAI API key is missing.', 422);
        }

        $request = $this->http()->withToken($apiKey);
        $org = $this->credentials()['organization'] ?? $this->providerConfig()['organization'] ?? null;
        if ($org) {
            $request = $request->withHeaders(['OpenAI-Organization' => (string) $org]);
        }

        return $request;
    }

    protected function endpoint(string $path): string
    {
        $base = rtrim((string) ($this->provider?->base_url ?: 'https://api.openai.com/v1'), '/');

        return $base.$path;
    }
}
