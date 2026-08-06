<?php

namespace App\Domains\Ai\Providers;

use App\Domains\Ai\Contracts\AiProviderInterface;
use App\Domains\Ai\DTOs\AiCategorizeRequest;
use App\Domains\Ai\DTOs\AiCategorizeResult;
use App\Domains\Ai\DTOs\AiChatRequest;
use App\Domains\Ai\DTOs\AiChatResult;
use App\Domains\Ai\DTOs\AiCompletionRequest;
use App\Domains\Ai\DTOs\AiCompletionResult;
use App\Domains\Ai\DTOs\AiEmbeddingRequest;
use App\Domains\Ai\DTOs\AiEmbeddingResult;
use App\Domains\Ai\DTOs\AiHealthResult;
use App\Domains\Ai\DTOs\AiTextRequest;
use App\Domains\Ai\DTOs\AiTextResult;
use App\Domains\Ai\DTOs\AiTranslateRequest;
use App\Domains\Ai\Models\AiProvider;
use App\Shared\Exceptions\ApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

abstract class AbstractHttpAiProvider implements AiProviderInterface
{
    protected ?AiProvider $provider = null;

    public function configure(AiProvider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    abstract public function driver(): string;

    public function summarize(AiTextRequest $request): AiTextResult
    {
        $chat = $this->chat(new AiChatRequest(
            messages: [
                ['role' => 'system', 'content' => 'Summarize the following text clearly and concisely.'],
                ['role' => 'user', 'content' => $request->text],
            ],
            model: $request->model,
            options: $request->options,
        ));

        return new AiTextResult(
            content: $chat->content,
            model: $chat->model,
            tokensIn: $chat->tokensIn,
            tokensOut: $chat->tokensOut,
            latencyMs: $chat->latencyMs,
            raw: $chat->raw,
        );
    }

    public function translate(AiTranslateRequest $request): AiTextResult
    {
        $source = $request->sourceLocale ? "from {$request->sourceLocale} " : '';
        $chat = $this->chat(new AiChatRequest(
            messages: [
                ['role' => 'system', 'content' => "Translate the user text {$source}to {$request->targetLocale}. Return only the translation."],
                ['role' => 'user', 'content' => $request->text],
            ],
            model: $request->model,
            options: $request->options,
        ));

        return new AiTextResult(
            content: $chat->content,
            model: $chat->model,
            tokensIn: $chat->tokensIn,
            tokensOut: $chat->tokensOut,
            latencyMs: $chat->latencyMs,
            raw: $chat->raw,
        );
    }

    public function categorize(AiCategorizeRequest $request): AiCategorizeResult
    {
        $labels = $request->labels !== [] ? implode(', ', $request->labels) : 'general topics';
        $chat = $this->chat(new AiChatRequest(
            messages: [
                ['role' => 'system', 'content' => "Classify the text into one or more of these labels: {$labels}. Respond as JSON array of objects with keys label and confidence (0-1)."],
                ['role' => 'user', 'content' => $request->text],
            ],
            model: $request->model,
            options: $request->options,
        ));

        $predictions = $this->parseCategorizeJson($chat->content, $request->labels);

        return new AiCategorizeResult(
            predictions: $predictions,
            model: $chat->model,
            tokensIn: $chat->tokensIn,
            tokensOut: $chat->tokensOut,
            latencyMs: $chat->latencyMs,
            raw: $chat->raw,
        );
    }

    public function complete(AiCompletionRequest $request): AiCompletionResult
    {
        $chat = $this->chat(new AiChatRequest(
            messages: [
                ['role' => 'user', 'content' => $request->prompt],
            ],
            model: $request->model,
            temperature: $request->temperature,
            maxTokens: $request->maxTokens,
            options: $request->options,
        ));

        return new AiCompletionResult(
            content: $chat->content,
            model: $chat->model,
            tokensIn: $chat->tokensIn,
            tokensOut: $chat->tokensOut,
            latencyMs: $chat->latencyMs,
            raw: $chat->raw,
        );
    }

    protected function http(): PendingRequest
    {
        $timeout = (int) ($this->provider?->timeout_seconds ?: config('ai.timeout', 30));

        return Http::timeout($timeout)
            ->acceptJson()
            ->asJson();
    }

    /**
     * @return array<string, mixed>
     */
    protected function credentials(): array
    {
        return is_array($this->provider?->credentials) ? $this->provider->credentials : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function providerConfig(): array
    {
        return is_array($this->provider?->config) ? $this->provider->config : [];
    }

    protected function requireConfigured(): void
    {
        if (! $this->provider) {
            throw new ApiException('AI provider is not configured.', 500);
        }
    }

    protected function resolveModel(?string $override, string $fallback): string
    {
        return $override
            ?: (string) ($this->provider?->default_model ?: $fallback);
    }

    /**
     * Turn provider HTTP error payloads into short operator-facing messages.
     */
    protected function providerHttpError(string $vendor, $response, string $operation = 'request'): ApiException
    {
        $status = $response->status();
        $body = $response->body();
        $json = $response->json();
        $remoteMessage = is_array($json)
            ? (string) (data_get($json, 'error.message')
                ?? data_get($json, 'error.msg')
                ?? data_get($json, 'message')
                ?? '')
            : '';

        $code = is_array($json) ? data_get($json, 'error.code') : null;
        $remoteStatus = is_array($json) ? (string) data_get($json, 'error.status', '') : '';

        if ($status === 429 || $remoteStatus === 'RESOURCE_EXHAUSTED' || $code === 429) {
            $hint = 'Quota or rate limit exceeded. Wait and retry, switch model, or check billing/plan in the provider console.';
            if (stripos($remoteMessage, 'free_tier') !== false || stripos($remoteMessage, 'free tier') !== false) {
                $hint = 'Free-tier quota exceeded for this model. Wait and retry, try another model (e.g. gemini-1.5-flash), or enable billing in Google AI Studio.';
            }

            return new ApiException("{$vendor} {$operation} failed: {$hint}", 429);
        }

        if ($status === 401 || $status === 403) {
            return new ApiException("{$vendor} {$operation} failed: invalid or unauthorized API key.", 401);
        }

        if ($status === 404) {
            return new ApiException("{$vendor} {$operation} failed: model or endpoint not found. Check the default model name.", 404);
        }

        $short = $remoteMessage !== ''
            ? Str::limit(preg_replace('/\s+/', ' ', $remoteMessage) ?? $remoteMessage, 240)
            : Str::limit(preg_replace('/\s+/', ' ', $body) ?? 'Unexpected provider error.', 240);

        return new ApiException("{$vendor} {$operation} failed: {$short}", $status >= 400 && $status < 600 ? $status : 502);
    }

    /**
     * @param  list<string>  $labels
     * @return list<array{label: string, confidence: float}>
     */
    protected function parseCategorizeJson(string $content, array $labels): array
    {
        $json = Str::of($content)->match('/(\[.*\])/s')->toString();
        if ($json === '') {
            $json = $content;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            $fallback = $labels[0] ?? 'uncategorized';

            return [['label' => $fallback, 'confidence' => 0.5]];
        }

        $predictions = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            $predictions[] = [
                'label' => (string) ($item['label'] ?? 'uncategorized'),
                'confidence' => (float) ($item['confidence'] ?? 0.5),
            ];
        }

        return $predictions !== [] ? $predictions : [['label' => $labels[0] ?? 'uncategorized', 'confidence' => 0.5]];
    }

    abstract public function chat(AiChatRequest $request): AiChatResult;

    abstract public function embed(AiEmbeddingRequest $request): AiEmbeddingResult;

    abstract public function testConnection(): AiHealthResult;
}
