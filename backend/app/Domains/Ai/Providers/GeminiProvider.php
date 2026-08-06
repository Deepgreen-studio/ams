<?php

namespace App\Domains\Ai\Providers;

use App\Domains\Ai\DTOs\AiChatRequest;
use App\Domains\Ai\DTOs\AiChatResult;
use App\Domains\Ai\DTOs\AiEmbeddingRequest;
use App\Domains\Ai\DTOs\AiEmbeddingResult;
use App\Domains\Ai\DTOs\AiHealthResult;
use App\Shared\Exceptions\ApiException;

class GeminiProvider extends AbstractHttpAiProvider
{
    public function driver(): string
    {
        return 'gemini';
    }

    public function testConnection(): AiHealthResult
    {
        $this->requireConfigured();
        $started = microtime(true);

        try {
            $model = $this->resolveModel(null, 'gemini-flash-latest');
            $response = $this->client()->get($this->endpoint('/models'));
            $latency = (int) ((microtime(true) - $started) * 1000);

            if (! $response->successful()) {
                return new AiHealthResult(false, 'Gemini health check failed: '.$response->status(), [
                    'body' => $response->json(),
                    'model' => $model,
                ], $latency);
            }

            return new AiHealthResult(true, 'Gemini connection successful.', [
                'model' => $model,
                'models' => count($response->json('models') ?? []),
            ], $latency);
        } catch (\Throwable $exception) {
            return new AiHealthResult(false, $exception->getMessage(), [], (int) ((microtime(true) - $started) * 1000));
        }
    }

    public function chat(AiChatRequest $request): AiChatResult
    {
        $this->requireConfigured();
        $model = $this->resolveModel($request->model, 'gemini-flash-latest');
        $started = microtime(true);

        $contents = [];
        $system = null;
        foreach ($request->messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $system = $message['content'] ?? '';

                continue;
            }
            $contents[] = [
                'role' => ($message['role'] ?? 'user') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) ($message['content'] ?? '')]],
            ];
        }

        $payload = ['contents' => $contents];
        if ($system) {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }
        if ($request->temperature !== null || $request->maxTokens !== null) {
            $payload['generationConfig'] = array_filter([
                'temperature' => $request->temperature,
                'maxOutputTokens' => $request->maxTokens,
            ], fn ($v) => $v !== null);
        }

        $response = $this->client()->post($this->endpoint("/models/{$model}:generateContent"), $payload);
        $latency = (int) ((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw $this->providerHttpError('Gemini', $response, 'chat');
        }

        $json = $response->json();

        return new AiChatResult(
            content: (string) data_get($json, 'candidates.0.content.parts.0.text', ''),
            model: $model,
            tokensIn: (int) data_get($json, 'usageMetadata.promptTokenCount', 0),
            tokensOut: (int) data_get($json, 'usageMetadata.candidatesTokenCount', 0),
            finishReason: data_get($json, 'candidates.0.finishReason'),
            requestId: null,
            latencyMs: $latency,
            raw: is_array($json) ? $json : [],
        );
    }

    public function embed(AiEmbeddingRequest $request): AiEmbeddingResult
    {
        $this->requireConfigured();
        $model = $request->model ?: (string) ($this->provider?->embedding_model ?: 'text-embedding-004');
        $started = microtime(true);

        $embeddings = [];
        foreach ($request->inputs as $input) {
            $response = $this->client()->post($this->endpoint("/models/{$model}:embedContent"), [
                'content' => ['parts' => [['text' => $input]]],
            ]);
            if (! $response->successful()) {
                throw $this->providerHttpError('Gemini', $response, 'embedding');
            }
            $embeddings[] = array_map('floatval', data_get($response->json(), 'embedding.values', []));
        }

        return new AiEmbeddingResult(
            embeddings: $embeddings,
            model: $model,
            tokensIn: 0,
            latencyMs: (int) ((microtime(true) - $started) * 1000),
            raw: [],
        );
    }

    protected function client()
    {
        $apiKey = (string) ($this->credentials()['api_key'] ?? '');
        if ($apiKey === '') {
            throw new ApiException('Gemini API key is missing.', 422);
        }

        return $this->http()->withQueryParameters(['key' => $apiKey]);
    }

    protected function endpoint(string $path): string
    {
        $base = rtrim((string) ($this->provider?->base_url ?: 'https://generativelanguage.googleapis.com/v1beta'), '/');

        return $base.$path;
    }
}
