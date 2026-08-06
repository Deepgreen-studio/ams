<?php

namespace App\Domains\Ai\Services;

use App\Domains\Ai\DTOs\AiCategorizeRequest;
use App\Domains\Ai\DTOs\AiChatRequest;
use App\Domains\Ai\DTOs\AiEmbeddingRequest;
use App\Domains\Ai\DTOs\AiTextRequest;
use App\Domains\Ai\DTOs\AiTranslateRequest;
use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Ai\Enums\AiMessageRole;
use App\Domains\Ai\Models\AiConversation;
use App\Domains\Ai\Models\AiMessage;
use App\Domains\Ai\Models\AiProvider;
use App\Domains\Ai\Repositories\AiConversationRepository;
use App\Domains\Ai\Repositories\AiMessageRepository;
use App\Domains\Ai\Repositories\AiPromptRepository;
use App\Domains\Ai\Repositories\AiProviderRepository;
use App\Domains\Ai\Repositories\AiUsageLogRepository;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class AiAssistantService
{
    public function __construct(
        private readonly AiProviderManager $providerManager,
        private readonly AiProviderRepository $providerRepository,
        private readonly AiPromptRepository $promptRepository,
        private readonly AiPromptService $promptService,
        private readonly AiConversationRepository $conversationRepository,
        private readonly AiMessageRepository $messageRepository,
        private readonly AiUsageLogRepository $usageLogRepository,
        private readonly CompanyRepository $companyRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateConversations(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['company_id']) && ! is_numeric($filters['company_id'])) {
            $filters['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $filters['company_id'])->id;
        }

        return $this->conversationRepository->paginateFiltered($filters);
    }

    public function findConversation(string $identifier): AiConversation
    {
        return $this->conversationRepository->findByIdentifierOrFail($identifier)
            ->load([
                'user:id,uuid,full_name,email',
                'provider:id,uuid,name,driver,slug',
                'prompt:id,uuid,key,name,feature',
                'messages',
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function chat(array $data, User $actor): array
    {
        $this->assertFeatureEnabled(AiFeature::ChatAssistant);

        return DB::transaction(function () use ($data, $actor): array {
            $provider = $this->resolveProvider($data['provider_id'] ?? null, $data['company_id'] ?? null);
            $driver = $this->providerManager->forProvider($provider);
            $feature = AiFeature::ChatAssistant;
            $message = trim((string) ($data['message'] ?? ''));
            if ($message === '') {
                throw new ApiException('Message is required.', 422);
            }

            $conversation = $this->resolveConversation($data, $actor, $provider, $feature);
            $history = $conversation->messages()
                ->get(['role', 'content'])
                ->map(fn (AiMessage $item) => [
                    'role' => $item->role instanceof AiMessageRole ? $item->role->value : (string) $item->role,
                    'content' => $item->content,
                ])
                ->values()
                ->all();

            $system = null;
            if ($conversation->ai_prompt_id) {
                $prompt = $this->promptRepository->find($conversation->ai_prompt_id);
                $system = $prompt?->system_prompt;
            }
            if (! $system) {
                $prompt = $this->promptRepository->findPublishedByFeature($feature, $conversation->company_id);
                $system = $prompt?->system_prompt;
                if ($prompt && ! $conversation->ai_prompt_id) {
                    $this->conversationRepository->update($conversation->id, ['ai_prompt_id' => $prompt->id]);
                }
            }

            $messages = [];
            if ($system) {
                $messages[] = ['role' => 'system', 'content' => $system];
            }
            $messages = array_merge($messages, $history, [
                ['role' => 'user', 'content' => $message],
            ]);

            $userMessage = $this->messageRepository->create([
                'ai_conversation_id' => $conversation->id,
                'role' => AiMessageRole::User->value,
                'content' => $message,
            ]);

            try {
                $started = microtime(true);
                $result = $driver->chat(new AiChatRequest(
                    messages: $messages,
                    model: $data['model'] ?? $provider->default_model,
                    temperature: isset($data['temperature']) ? (float) $data['temperature'] : null,
                    maxTokens: isset($data['max_tokens']) ? (int) $data['max_tokens'] : null,
                ));
                $latency = (int) round((microtime(true) - $started) * 1000);

                $assistantMessage = $this->messageRepository->create([
                    'ai_conversation_id' => $conversation->id,
                    'role' => AiMessageRole::Assistant->value,
                    'content' => $result->content,
                    'token_input' => $result->tokensIn,
                    'token_output' => $result->tokensOut,
                    'model' => $result->model,
                    'finish_reason' => $result->finishReason,
                    'metadata' => ['raw' => $result->raw],
                ]);

                if (! $conversation->title) {
                    $this->conversationRepository->update($conversation->id, [
                        'title' => mb_substr($message, 0, 80),
                    ]);
                }

                $this->logUsage([
                    'company_id' => $conversation->company_id,
                    'user_id' => $actor->id,
                    'ai_provider_id' => $provider->id,
                    'ai_conversation_id' => $conversation->id,
                    'ai_message_id' => $assistantMessage->id,
                    'feature' => $feature->value,
                    'operation' => 'chat',
                    'driver' => $driver->driver(),
                    'model' => $result->model,
                    'tokens_in' => $result->tokensIn,
                    'tokens_out' => $result->tokensOut,
                    'latency_ms' => $result->latencyMs ?: $latency,
                    'status' => 'success',
                    'request_id' => $result->requestId,
                ]);

                return [
                    'conversation' => $this->findConversation($conversation->uuid),
                    'user_message' => $userMessage->fresh(),
                    'assistant_message' => $assistantMessage->fresh(),
                    'reply' => $result->content,
                ];
            } catch (Throwable $e) {
                $this->logUsage([
                    'company_id' => $conversation->company_id,
                    'user_id' => $actor->id,
                    'ai_provider_id' => $provider->id,
                    'ai_conversation_id' => $conversation->id,
                    'ai_message_id' => $userMessage->id,
                    'feature' => $feature->value,
                    'operation' => 'chat',
                    'driver' => $driver->driver(),
                    'model' => $provider->default_model,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function suggest(array $data, User $actor): array
    {
        return $this->runTextFeature(
            AiFeature::Suggestions,
            $actor,
            $data,
            'suggest',
            function ($driver, AiProvider $provider, string $text, ?string $model) {
                $result = $driver->complete(new \App\Domains\Ai\DTOs\AiCompletionRequest(
                    prompt: "Provide actionable suggestions for:\n\n{$text}",
                    model: $model ?: $provider->default_model,
                ));

                return [
                    'content' => $result->content,
                    'model' => $result->model,
                    'tokens_in' => $result->tokensIn,
                    'tokens_out' => $result->tokensOut,
                    'latency_ms' => $result->latencyMs,
                ];
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function categorize(array $data, User $actor): array
    {
        $this->assertFeatureEnabled(AiFeature::AutoCategorization);
        $text = trim((string) ($data['text'] ?? ''));
        $labels = array_values(array_filter((array) ($data['labels'] ?? [])));
        if ($text === '' || $labels === []) {
            throw new ApiException('Text and labels are required for categorization.', 422);
        }

        $provider = $this->resolveProvider($data['provider_id'] ?? null, $data['company_id'] ?? null);
        $driver = $this->providerManager->forProvider($provider);

        try {
            $result = $driver->categorize(new AiCategorizeRequest(
                text: $text,
                labels: $labels,
                model: $data['model'] ?? $provider->default_model,
            ));
            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => AiFeature::AutoCategorization->value,
                'operation' => 'categorize',
                'driver' => $driver->driver(),
                'model' => $result->model,
                'tokens_in' => $result->tokensIn,
                'tokens_out' => $result->tokensOut,
                'latency_ms' => $result->latencyMs,
                'status' => 'success',
            ]);

            return [
                'predictions' => $result->predictions,
                'model' => $result->model,
            ];
        } catch (Throwable $e) {
            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => AiFeature::AutoCategorization->value,
                'operation' => 'categorize',
                'driver' => $driver->driver(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function routeTicket(array $data, User $actor): array
    {
        $this->assertFeatureEnabled(AiFeature::SmartTicketRouting);
        $subject = trim((string) ($data['subject'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $teams = array_values(array_filter((array) ($data['teams'] ?? ['support', 'billing', 'technical', 'sales'])));
        $text = trim($subject."\n".$description);
        if ($text === '') {
            throw new ApiException('Ticket subject or description is required.', 422);
        }

        $provider = $this->resolveProvider($data['provider_id'] ?? null, $data['company_id'] ?? null);
        $driver = $this->providerManager->forProvider($provider);
        $prompt = $this->promptRepository->findPublishedByFeature(AiFeature::SmartTicketRouting, $provider->company_id);
        $rendered = $prompt
            ? $this->promptService->render($prompt, [
                'subject' => $subject,
                'description' => $description,
                'teams' => implode(', ', $teams),
            ])
            : "Route this support ticket to the best team.\nSubject: {$subject}\nDescription: {$description}\nTeams: ".implode(', ', $teams);

        try {
            $category = $driver->categorize(new AiCategorizeRequest(
                text: $text,
                labels: $teams,
                model: $data['model'] ?? $provider->default_model,
            ));
            $suggestion = $driver->complete(new \App\Domains\Ai\DTOs\AiCompletionRequest(
                prompt: $rendered."\n\nAlso suggest priority (low, medium, high, critical).",
                model: $data['model'] ?? $provider->default_model,
            ));

            $team = $category->predictions[0]['label'] ?? ($teams[0] ?? 'support');
            $priority = 'medium';
            foreach (['critical', 'high', 'medium', 'low'] as $candidate) {
                if (stripos($suggestion->content, $candidate) !== false) {
                    $priority = $candidate;
                    break;
                }
            }

            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => AiFeature::SmartTicketRouting->value,
                'operation' => 'route_ticket',
                'driver' => $driver->driver(),
                'model' => $suggestion->model,
                'tokens_in' => $category->tokensIn + $suggestion->tokensIn,
                'tokens_out' => $category->tokensOut + $suggestion->tokensOut,
                'latency_ms' => ($category->latencyMs ?? 0) + ($suggestion->latencyMs ?? 0),
                'status' => 'success',
            ]);

            return [
                'recommended_team' => $team,
                'recommended_priority' => $priority,
                'confidence' => $category->predictions[0]['confidence'] ?? null,
                'rationale' => $suggestion->content,
                'predictions' => $category->predictions,
            ];
        } catch (Throwable $e) {
            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => AiFeature::SmartTicketRouting->value,
                'operation' => 'route_ticket',
                'driver' => $driver->driver(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function contentSuggestions(array $data, User $actor): array
    {
        return $this->runTextFeature(
            AiFeature::ContentSuggestions,
            $actor,
            $data,
            'content_suggestions',
            function ($driver, AiProvider $provider, string $text, ?string $model) {
                $result = $driver->complete(new \App\Domains\Ai\DTOs\AiCompletionRequest(
                    prompt: "Suggest SEO title, meta description, and content improvements for:\n\n{$text}",
                    model: $model ?: $provider->default_model,
                ));

                return [
                    'content' => $result->content,
                    'model' => $result->model,
                    'tokens_in' => $result->tokensIn,
                    'tokens_out' => $result->tokensOut,
                    'latency_ms' => $result->latencyMs,
                ];
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function translate(array $data, User $actor): array
    {
        $this->assertFeatureEnabled(AiFeature::AutoTranslation);
        $text = trim((string) ($data['text'] ?? ''));
        $target = trim((string) ($data['target_locale'] ?? ''));
        if ($text === '' || $target === '') {
            throw new ApiException('Text and target_locale are required.', 422);
        }

        $provider = $this->resolveProvider($data['provider_id'] ?? null, $data['company_id'] ?? null);
        $driver = $this->providerManager->forProvider($provider);

        try {
            $result = $driver->translate(new AiTranslateRequest(
                text: $text,
                targetLocale: $target,
                sourceLocale: $data['source_locale'] ?? null,
                model: $data['model'] ?? $provider->default_model,
            ));
            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => AiFeature::AutoTranslation->value,
                'operation' => 'translate',
                'driver' => $driver->driver(),
                'model' => $result->model,
                'tokens_in' => $result->tokensIn,
                'tokens_out' => $result->tokensOut,
                'latency_ms' => $result->latencyMs,
                'status' => 'success',
            ]);

            return [
                'content' => $result->content,
                'model' => $result->model,
                'target_locale' => $target,
            ];
        } catch (Throwable $e) {
            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => AiFeature::AutoTranslation->value,
                'operation' => 'translate',
                'driver' => $driver->driver(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function summarize(array $data, User $actor): array
    {
        return $this->runTextFeature(
            AiFeature::DocumentSummarization,
            $actor,
            $data,
            'summarize',
            function ($driver, AiProvider $provider, string $text, ?string $model) {
                $result = $driver->summarize(new AiTextRequest(
                    text: $text,
                    model: $model ?: $provider->default_model,
                ));

                return [
                    'content' => $result->content,
                    'model' => $result->model,
                    'tokens_in' => $result->tokensIn,
                    'tokens_out' => $result->tokensOut,
                    'latency_ms' => $result->latencyMs,
                ];
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function search(array $data, User $actor): array
    {
        $this->assertFeatureEnabled(AiFeature::AiSearch);
        $query = trim((string) ($data['query'] ?? ''));
        $corpus = array_values(array_filter(array_map('strval', (array) ($data['documents'] ?? []))));
        if ($query === '') {
            throw new ApiException('Search query is required.', 422);
        }
        if ($corpus === []) {
            throw new ApiException('At least one document is required for AI search.', 422);
        }

        $provider = $this->resolveProvider($data['provider_id'] ?? null, $data['company_id'] ?? null);
        $driver = $this->providerManager->forProvider($provider);

        try {
            $embedded = $driver->embed(new AiEmbeddingRequest(
                inputs: array_merge([$query], $corpus),
                model: $data['model'] ?? $provider->embedding_model,
            ));
            $queryVector = $embedded->embeddings[0] ?? [];
            $ranked = [];
            foreach ($corpus as $index => $document) {
                $docVector = $embedded->embeddings[$index + 1] ?? [];
                $ranked[] = [
                    'index' => $index,
                    'document' => $document,
                    'score' => $this->cosineSimilarity($queryVector, $docVector),
                ];
            }
            usort($ranked, fn ($a, $b) => $b['score'] <=> $a['score']);

            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => AiFeature::AiSearch->value,
                'operation' => 'search',
                'driver' => $driver->driver(),
                'model' => $embedded->model,
                'tokens_in' => $embedded->tokensIn,
                'tokens_out' => 0,
                'latency_ms' => $embedded->latencyMs,
                'status' => 'success',
            ]);

            return [
                'query' => $query,
                'results' => array_slice($ranked, 0, (int) ($data['limit'] ?? 5)),
                'model' => $embedded->model,
            ];
        } catch (Throwable $e) {
            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => AiFeature::AiSearch->value,
                'operation' => 'search',
                'driver' => $driver->driver(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function knowledge(array $data, User $actor): array
    {
        $question = trim((string) ($data['question'] ?? $data['text'] ?? ''));
        if ($question === '') {
            throw new ApiException('Question is required.', 422);
        }

        $payload = $data;
        $payload['text'] = $question;
        $payload['message'] = $question;

        return $this->runTextFeature(
            AiFeature::KnowledgeAssistant,
            $actor,
            $payload,
            'knowledge',
            function ($driver, AiProvider $provider, string $text, ?string $model) {
                $result = $driver->chat(new AiChatRequest(
                    messages: [
                        ['role' => 'system', 'content' => 'You are a knowledge assistant for an enterprise application management platform. Answer clearly and cite assumptions.'],
                        ['role' => 'user', 'content' => $text],
                    ],
                    model: $model ?: $provider->default_model,
                ));

                return [
                    'content' => $result->content,
                    'model' => $result->model,
                    'tokens_in' => $result->tokensIn,
                    'tokens_out' => $result->tokensOut,
                    'latency_ms' => $result->latencyMs,
                ];
            }
        );
    }

    public function archiveConversation(string $identifier, User $actor): AiConversation
    {
        $conversation = $this->conversationRepository->findByIdentifierOrFail($identifier);
        $this->conversationRepository->update($conversation->id, ['status' => 'archived']);

        return $this->findConversation($conversation->uuid);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable  $callback
     * @return array<string, mixed>
     */
    protected function runTextFeature(AiFeature $feature, User $actor, array $data, string $operation, callable $callback): array
    {
        $this->assertFeatureEnabled($feature);
        $text = trim((string) ($data['text'] ?? $data['content'] ?? $data['message'] ?? ''));
        if ($text === '') {
            throw new ApiException('Text content is required.', 422);
        }

        $provider = $this->resolveProvider($data['provider_id'] ?? null, $data['company_id'] ?? null);
        $driver = $this->providerManager->forProvider($provider);

        try {
            $result = $callback($driver, $provider, $text, $data['model'] ?? null);
            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => $feature->value,
                'operation' => $operation,
                'driver' => $driver->driver(),
                'model' => $result['model'] ?? $provider->default_model,
                'tokens_in' => $result['tokens_in'] ?? 0,
                'tokens_out' => $result['tokens_out'] ?? 0,
                'latency_ms' => $result['latency_ms'] ?? null,
                'status' => 'success',
            ]);

            return [
                'feature' => $feature->value,
                'content' => $result['content'] ?? null,
                'model' => $result['model'] ?? null,
                'meta' => $result['meta'] ?? [],
            ];
        } catch (Throwable $e) {
            $this->logUsage([
                'company_id' => $provider->company_id,
                'user_id' => $actor->id,
                'ai_provider_id' => $provider->id,
                'feature' => $feature->value,
                'operation' => $operation,
                'driver' => $driver->driver(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveConversation(array $data, User $actor, AiProvider $provider, AiFeature $feature): AiConversation
    {
        if (! empty($data['conversation_id'])) {
            $conversation = $this->conversationRepository->findByIdentifierOrFail((string) $data['conversation_id']);
            if ((int) $conversation->user_id !== (int) $actor->id && ! $actor->can('ai.manage')) {
                throw new ApiException('You cannot continue this conversation.', 403);
            }

            return $conversation;
        }

        $companyId = $provider->company_id;
        if (! empty($data['company_id'])) {
            $companyId = is_numeric($data['company_id'])
                ? (int) $data['company_id']
                : $this->companyRepository->findByIdentifierOrFail((string) $data['company_id'])->id;
        }

        /** @var AiConversation $conversation */
        $conversation = $this->conversationRepository->create([
            'company_id' => $companyId,
            'user_id' => $actor->id,
            'ai_provider_id' => $provider->id,
            'feature' => $feature->value,
            'context_type' => $data['context_type'] ?? null,
            'context_id' => $data['context_id'] ?? null,
            'title' => $data['title'] ?? null,
            'status' => 'active',
            'metadata' => $data['metadata'] ?? null,
        ]);

        return $conversation;
    }

    protected function resolveProvider(mixed $providerId, mixed $companyId = null): AiProvider
    {
        if (! blank($providerId)) {
            return $this->providerRepository->findByIdentifierOrFail((string) $providerId);
        }

        $resolvedCompanyId = null;
        if (! blank($companyId)) {
            $resolvedCompanyId = is_numeric($companyId)
                ? (int) $companyId
                : $this->companyRepository->findByIdentifierOrFail((string) $companyId)->id;
        }

        $provider = $this->providerRepository->findDefault($resolvedCompanyId);
        if (! $provider) {
            throw new ApiException('No AI provider is configured. Create a provider in AI Settings.', 422);
        }

        return $provider;
    }

    protected function assertFeatureEnabled(AiFeature $feature): void
    {
        if (! config('ai.features.'.$feature->value, true)) {
            throw new ApiException("AI feature [{$feature->value}] is disabled.", 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function logUsage(array $payload): void
    {
        $this->usageLogRepository->create(array_merge([
            'tokens_in' => 0,
            'tokens_out' => 0,
            'status' => 'success',
        ], $payload));
    }

    /**
     * @param  list<float|int>  $a
     * @param  list<float|int>  $b
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot += ((float) $a[$i]) * ((float) $b[$i]);
            $normA += ((float) $a[$i]) ** 2;
            $normB += ((float) $b[$i]) ** 2;
        }
        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
