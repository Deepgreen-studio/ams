<?php

namespace App\Domains\Ai\Providers;

use App\Domains\Ai\DTOs\AiChatRequest;
use App\Domains\Ai\DTOs\AiChatResult;
use App\Domains\Ai\DTOs\AiEmbeddingRequest;
use App\Domains\Ai\DTOs\AiEmbeddingResult;
use App\Domains\Ai\DTOs\AiHealthResult;
use App\Shared\Exceptions\ApiException;

class ClaudeProvider extends AbstractHttpAiProvider
{
    public function driver(): string
    {
        return 'claude';
    }

    public function testConnection(): AiHealthResult
    {
        $this->requireConfigured();
        $apiKey = (string) ($this->credentials()['api_key'] ?? '');
        if ($apiKey === '') {
            return new AiHealthResult(false, 'Anthropic API key is missing.', []);
        }

        // Anthropic has no lightweight models list; validate credential presence + endpoint reachability via tiny message.
        try {
            $result = $this->chat(new AiChatRequest(
                messages: [['role' => 'user', 'content' => 'ping']],
                maxTokens: 8,
            ));

            return new AiHealthResult(true, 'Claude connection successful.', [
                'model' => $result->model,
            ], $result->latencyMs);
        } catch (\Throwable $exception) {
            return new AiHealthResult(false, $exception->getMessage(), []);
        }
    }

    public function chat(AiChatRequest $request): AiChatResult
    {
        $this->requireConfigured();
        $model = $this->resolveModel($request->model, 'claude-3-5-sonnet-latest');
        $started = microtime(true);

        $system = null;
        $messages = [];
        foreach ($request->messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $system = (string) ($message['content'] ?? '');

                continue;
            }
            $messages[] = [
                'role' => ($message['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $request->maxTokens ?? (int) config('ai.max_tokens', 2048),
            'messages' => $messages,
        ];
        if ($system) {
            $payload['system'] = $system;
        }
        if ($request->temperature !== null) {
            $payload['temperature'] = $request->temperature;
        }

        $response = $this->client()->post($this->endpoint('/messages'), $payload);
        $latency = (int) ((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw $this->providerHttpError('Claude', $response, 'chat');
        }

        $json = $response->json();
        $content = collect($json['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        return new AiChatResult(
            content: $content,
            model: (string) data_get($json, 'model', $model),
            tokensIn: (int) data_get($json, 'usage.input_tokens', 0),
            tokensOut: (int) data_get($json, 'usage.output_tokens', 0),
            finishReason: data_get($json, 'stop_reason'),
            requestId: $response->header('request-id'),
            latencyMs: $latency,
            raw: is_array($json) ? $json : [],
        );
    }

    public function embed(AiEmbeddingRequest $request): AiEmbeddingResult
    {
        throw new ApiException('Anthropic Claude embeddings are not supported via this driver. Use an embedding-capable provider.', 422);
    }

    protected function client()
    {
        $apiKey = (string) ($this->credentials()['api_key'] ?? '');
        if ($apiKey === '') {
            throw new ApiException('Anthropic API key is missing.', 422);
        }

        return $this->http()->withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => (string) ($this->providerConfig()['api_version'] ?? '2023-06-01'),
        ]);
    }

    protected function endpoint(string $path): string
    {
        $base = rtrim((string) ($this->provider?->base_url ?: 'https://api.anthropic.com/v1'), '/');

        return $base.$path;
    }
}
