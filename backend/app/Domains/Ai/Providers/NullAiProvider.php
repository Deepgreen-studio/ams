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

/**
 * Local stub provider for development/tests when no external AI credentials exist.
 */
class NullAiProvider implements AiProviderInterface
{
    protected ?AiProvider $provider = null;

    public function driver(): string
    {
        return 'null';
    }

    public function configure(AiProvider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function testConnection(): AiHealthResult
    {
        return new AiHealthResult(true, 'Null AI provider is ready (stub).', [
            'provider' => $this->provider?->slug,
        ]);
    }

    public function chat(AiChatRequest $request): AiChatResult
    {
        $lastUser = collect($request->messages)->where('role', 'user')->last();
        $prompt = (string) ($lastUser['content'] ?? '');

        return new AiChatResult(
            content: '[AI Stub] '.$this->stubReply($prompt),
            model: $request->model ?: 'null-model',
            tokensIn: max(1, (int) ceil(strlen($prompt) / 4)),
            tokensOut: 24,
            finishReason: 'stop',
            requestId: 'null-'.uniqid(),
            latencyMs: 5,
            raw: ['stub' => true],
        );
    }

    public function complete(AiCompletionRequest $request): AiCompletionResult
    {
        $chat = $this->chat(new AiChatRequest(
            messages: [['role' => 'user', 'content' => $request->prompt]],
            model: $request->model,
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

    public function embed(AiEmbeddingRequest $request): AiEmbeddingResult
    {
        $embeddings = array_map(function (string $input): array {
            $hash = crc32($input);

            return [($hash % 100) / 100, (($hash >> 8) % 100) / 100, (($hash >> 16) % 100) / 100];
        }, $request->inputs);

        return new AiEmbeddingResult(
            embeddings: $embeddings,
            model: $request->model ?: 'null-embed',
            tokensIn: count($request->inputs),
            latencyMs: 3,
            raw: ['stub' => true],
        );
    }

    public function summarize(AiTextRequest $request): AiTextResult
    {
        $summary = mb_substr(trim($request->text), 0, 180).(mb_strlen($request->text) > 180 ? '…' : '');

        return new AiTextResult(
            content: '[Summary Stub] '.$summary,
            model: $request->model ?: 'null-model',
            tokensIn: max(1, (int) ceil(strlen($request->text) / 4)),
            tokensOut: 20,
            latencyMs: 4,
        );
    }

    public function translate(AiTranslateRequest $request): AiTextResult
    {
        return new AiTextResult(
            content: '[Translation Stub → '.$request->targetLocale.'] '.$request->text,
            model: $request->model ?: 'null-model',
            tokensIn: max(1, (int) ceil(strlen($request->text) / 4)),
            tokensOut: 20,
            latencyMs: 4,
        );
    }

    public function categorize(AiCategorizeRequest $request): AiCategorizeResult
    {
        $label = $request->labels[0] ?? 'general';
        foreach ($request->labels as $candidate) {
            if (stripos($request->text, $candidate) !== false) {
                $label = $candidate;
                break;
            }
        }

        return new AiCategorizeResult(
            predictions: [['label' => $label, 'confidence' => 0.72]],
            model: $request->model ?: 'null-model',
            tokensIn: max(1, (int) ceil(strlen($request->text) / 4)),
            tokensOut: 10,
            latencyMs: 3,
        );
    }

    private function stubReply(string $prompt): string
    {
        if ($prompt === '') {
            return 'Ready to assist.';
        }

        return 'Processed request: '.mb_substr($prompt, 0, 240);
    }
}
