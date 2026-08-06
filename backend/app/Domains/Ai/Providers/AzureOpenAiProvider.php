<?php

namespace App\Domains\Ai\Providers;

use App\Domains\Ai\DTOs\AiChatRequest;
use App\Domains\Ai\DTOs\AiChatResult;
use App\Domains\Ai\DTOs\AiEmbeddingRequest;
use App\Domains\Ai\DTOs\AiEmbeddingResult;
use App\Domains\Ai\DTOs\AiHealthResult;
use App\Shared\Exceptions\ApiException;

class AzureOpenAiProvider extends AbstractHttpAiProvider
{
    public function driver(): string
    {
        return 'azure_openai';
    }

    public function testConnection(): AiHealthResult
    {
        $this->requireConfigured();
        $started = microtime(true);

        try {
            // Lightweight chat probe with empty-safe stub when no key.
            $apiKey = (string) ($this->credentials()['api_key'] ?? '');
            if ($apiKey === '' || blank($this->provider?->base_url)) {
                return new AiHealthResult(false, 'Azure OpenAI requires base_url and api_key.', [], 0);
            }

            $response = $this->client()->get($this->modelsEndpoint());
            $latency = (int) ((microtime(true) - $started) * 1000);

            return new AiHealthResult(
                $response->successful(),
                $response->successful() ? 'Azure OpenAI connection successful.' : 'Azure OpenAI health check failed.',
                ['status' => $response->status()],
                $latency,
            );
        } catch (\Throwable $exception) {
            return new AiHealthResult(false, $exception->getMessage(), [], (int) ((microtime(true) - $started) * 1000));
        }
    }

    public function chat(AiChatRequest $request): AiChatResult
    {
        $this->requireConfigured();
        $deployment = $this->resolveModel($request->model, (string) ($this->providerConfig()['deployment'] ?? 'gpt-4o-mini'));
        $started = microtime(true);

        $payload = [
            'messages' => $request->messages,
        ];
        if ($request->temperature !== null) {
            $payload['temperature'] = $request->temperature;
        }
        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        $response = $this->client()->post($this->chatEndpoint($deployment), $payload);
        $latency = (int) ((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw $this->providerHttpError('Azure OpenAI', $response, 'chat');
        }

        $json = $response->json();

        return new AiChatResult(
            content: (string) data_get($json, 'choices.0.message.content', ''),
            model: (string) data_get($json, 'model', $deployment),
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
        $deployment = $request->model ?: (string) ($this->provider?->embedding_model ?: 'text-embedding-3-small');
        $started = microtime(true);

        $response = $this->client()->post($this->embeddingsEndpoint($deployment), [
            'input' => $request->inputs,
        ]);
        $latency = (int) ((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw $this->providerHttpError('Azure OpenAI', $response, 'embedding');
        }

        $json = $response->json();
        $embeddings = collect($json['data'] ?? [])->pluck('embedding')->map(fn ($item) => array_map('floatval', $item))->values()->all();

        return new AiEmbeddingResult(
            embeddings: $embeddings,
            model: $deployment,
            tokensIn: (int) data_get($json, 'usage.prompt_tokens', 0),
            latencyMs: $latency,
            raw: is_array($json) ? $json : [],
        );
    }

    protected function client()
    {
        $apiKey = (string) ($this->credentials()['api_key'] ?? '');
        if ($apiKey === '') {
            throw new ApiException('Azure OpenAI API key is missing.', 422);
        }

        return $this->http()->withHeaders([
            'api-key' => $apiKey,
        ]);
    }

    protected function apiVersion(): string
    {
        return (string) ($this->providerConfig()['api_version'] ?? '2024-06-01');
    }

    protected function chatEndpoint(string $deployment): string
    {
        $base = rtrim((string) $this->provider?->base_url, '/');

        return "{$base}/openai/deployments/{$deployment}/chat/completions?api-version=".$this->apiVersion();
    }

    protected function embeddingsEndpoint(string $deployment): string
    {
        $base = rtrim((string) $this->provider?->base_url, '/');

        return "{$base}/openai/deployments/{$deployment}/embeddings?api-version=".$this->apiVersion();
    }

    protected function modelsEndpoint(): string
    {
        $base = rtrim((string) $this->provider?->base_url, '/');

        return "{$base}/openai/models?api-version=".$this->apiVersion();
    }
}
