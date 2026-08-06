<?php

namespace App\Domains\Ai\Controllers;

use App\Domains\Ai\Models\AiProvider;
use App\Domains\Ai\Requests\AiChatRequest as ChatAiRequest;
use App\Domains\Ai\Requests\AiFeatureRequest;
use App\Domains\Ai\Requests\IndexAiRequest;
use App\Domains\Ai\Requests\StoreAiPromptRequest;
use App\Domains\Ai\Requests\StoreAiProviderRequest;
use App\Domains\Ai\Requests\UpdateAiPromptRequest;
use App\Domains\Ai\Requests\UpdateAiProviderRequest;
use App\Domains\Ai\Requests\UpdateAiSettingsRequest;
use App\Domains\Ai\Resources\AiConversationResource;
use App\Domains\Ai\Resources\AiMessageResource;
use App\Domains\Ai\Resources\AiPromptResource;
use App\Domains\Ai\Resources\AiProviderResource;
use App\Domains\Ai\Resources\AiUsageLogResource;
use App\Domains\Ai\Services\AiAnalyticsService;
use App\Domains\Ai\Services\AiAssistantService;
use App\Domains\Ai\Services\AiPromptService;
use App\Domains\Ai\Services\AiProviderService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class AiController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AiProviderService $providerService,
        private readonly AiPromptService $promptService,
        private readonly AiAssistantService $assistantService,
        private readonly AiAnalyticsService $analyticsService,
    ) {}

    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);
        $data = $this->analyticsService->dashboard();

        return ApiResponse::success([
            'provider_statistics' => $data['provider_statistics'],
            'prompt_statistics' => $data['prompt_statistics'],
            'conversation_statistics' => $data['conversation_statistics'],
            'usage_statistics' => $data['usage_statistics'],
            'usage_analytics' => $data['usage_analytics'],
            'catalog' => $data['catalog'],
            'recent_logs' => AiUsageLogResource::collection($data['recent_logs'])->resolve(),
            'recent_conversations' => AiConversationResource::collection($data['recent_conversations'])->resolve(),
        ]);
    }

    public function catalog(): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);

        return ApiResponse::success(['catalog' => $this->providerService->catalog()]);
    }

    public function analytics(IndexAiRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);
        $days = (int) ($request->validated('days') ?? 30);

        return ApiResponse::success([
            'analytics' => $this->analyticsService->usageAnalytics($days),
            'usage_statistics' => $this->analyticsService->dashboard()['usage_statistics'],
        ]);
    }

    public function settings(): JsonResponse
    {
        $this->authorize('manage', AiProvider::class);

        return ApiResponse::success($this->analyticsService->settings());
    }

    public function updateSettings(UpdateAiSettingsRequest $request): JsonResponse
    {
        $this->authorize('manage', AiProvider::class);
        $data = $this->analyticsService->updateSettings($request->validated());

        return ApiResponse::success($data, 'AI settings updated.');
    }

    public function providers(IndexAiRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);
        $paginator = $this->providerService->paginate($request->filters());

        return ApiResponse::success([
            'providers' => [
                'items' => AiProviderResource::collection($paginator->items())->resolve(),
                'meta' => $this->meta($paginator),
            ],
            'catalog' => $this->providerService->catalog(),
        ]);
    }

    public function showProvider(string $provider): JsonResponse
    {
        $model = $this->providerService->find($provider);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'provider' => new AiProviderResource($model),
            'catalog' => $this->providerService->catalog(),
        ]);
    }

    public function storeProvider(StoreAiProviderRequest $request): JsonResponse
    {
        $this->authorize('create', AiProvider::class);
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->providerService->create($request->validated(), $actor);

        return ApiResponse::success([
            'provider' => new AiProviderResource($model),
        ], 'AI provider created.', 201);
    }

    public function updateProvider(UpdateAiProviderRequest $request, string $provider): JsonResponse
    {
        $existing = $this->providerService->find($provider);
        $this->authorize('update', $existing);
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->providerService->update($provider, $request->validated(), $actor);

        return ApiResponse::success([
            'provider' => new AiProviderResource($model),
        ], 'AI provider updated.');
    }

    public function destroyProvider(string $provider): JsonResponse
    {
        $existing = $this->providerService->find($provider);
        $this->authorize('delete', $existing);
        /** @var User $actor */
        $actor = request()->user();
        $this->providerService->delete($provider, $actor);

        return ApiResponse::success(null, 'AI provider deleted.');
    }

    public function testProvider(string $provider): JsonResponse
    {
        $existing = $this->providerService->find($provider);
        $this->authorize('update', $existing);

        return ApiResponse::success([
            'result' => $this->providerService->testConnection($provider),
        ], 'Connection test completed.');
    }

    public function prompts(IndexAiRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);
        $paginator = $this->promptService->paginate($request->filters());

        return ApiResponse::success([
            'prompts' => [
                'items' => AiPromptResource::collection($paginator->items())->resolve(),
                'meta' => $this->meta($paginator),
            ],
            'catalog' => $this->providerService->catalog(),
        ]);
    }

    public function showPrompt(string $prompt): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);
        $model = $this->promptService->find($prompt);

        return ApiResponse::success([
            'prompt' => new AiPromptResource($model),
            'catalog' => $this->providerService->catalog(),
        ]);
    }

    public function storePrompt(StoreAiPromptRequest $request): JsonResponse
    {
        $this->authorize('create', AiProvider::class);
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->promptService->create($request->validated(), $actor);

        return ApiResponse::success([
            'prompt' => new AiPromptResource($model),
        ], 'AI prompt created.', 201);
    }

    public function updatePrompt(UpdateAiPromptRequest $request, string $prompt): JsonResponse
    {
        $this->authorize('manage', AiProvider::class);
        /** @var User $actor */
        $actor = $request->user();
        $model = $this->promptService->update($prompt, $request->validated(), $actor);

        return ApiResponse::success([
            'prompt' => new AiPromptResource($model),
        ], 'AI prompt updated.');
    }

    public function destroyPrompt(string $prompt): JsonResponse
    {
        $this->authorize('manage', AiProvider::class);
        /** @var User $actor */
        $actor = request()->user();
        $this->promptService->delete($prompt, $actor);

        return ApiResponse::success(null, 'AI prompt deleted.');
    }

    public function publishPrompt(string $prompt): JsonResponse
    {
        $this->authorize('manage', AiProvider::class);
        /** @var User $actor */
        $actor = request()->user();
        $model = $this->promptService->publish($prompt, $actor);

        return ApiResponse::success([
            'prompt' => new AiPromptResource($model),
        ], 'AI prompt published.');
    }

    public function conversations(IndexAiRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);
        $paginator = $this->assistantService->paginateConversations($request->filters());

        return ApiResponse::success([
            'conversations' => [
                'items' => AiConversationResource::collection($paginator->items())->resolve(),
                'meta' => $this->meta($paginator),
            ],
        ]);
    }

    public function showConversation(string $conversation): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);
        $model = $this->assistantService->findConversation($conversation);

        return ApiResponse::success([
            'conversation' => new AiConversationResource($model),
        ]);
    }

    public function archiveConversation(string $conversation): JsonResponse
    {
        $this->authorize('chat', AiProvider::class);
        /** @var User $actor */
        $actor = request()->user();
        $model = $this->assistantService->archiveConversation($conversation, $actor);

        return ApiResponse::success([
            'conversation' => new AiConversationResource($model),
        ], 'Conversation archived.');
    }

    public function chat(ChatAiRequest $request): JsonResponse
    {
        $this->authorize('chat', AiProvider::class);
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->assistantService->chat($request->validated(), $actor);

        return ApiResponse::success([
            'conversation' => new AiConversationResource($result['conversation']),
            'user_message' => new AiMessageResource($result['user_message']),
            'assistant_message' => new AiMessageResource($result['assistant_message']),
            'reply' => $result['reply'],
        ]);
    }

    public function suggest(AiFeatureRequest $request): JsonResponse
    {
        return $this->featureResponse($request, 'suggest');
    }

    public function categorize(AiFeatureRequest $request): JsonResponse
    {
        return $this->featureResponse($request, 'categorize');
    }

    public function routeTicket(AiFeatureRequest $request): JsonResponse
    {
        return $this->featureResponse($request, 'routeTicket');
    }

    public function contentSuggestions(AiFeatureRequest $request): JsonResponse
    {
        return $this->featureResponse($request, 'contentSuggestions');
    }

    public function translate(AiFeatureRequest $request): JsonResponse
    {
        return $this->featureResponse($request, 'translate');
    }

    public function summarize(AiFeatureRequest $request): JsonResponse
    {
        return $this->featureResponse($request, 'summarize');
    }

    public function search(AiFeatureRequest $request): JsonResponse
    {
        return $this->featureResponse($request, 'search');
    }

    public function knowledge(AiFeatureRequest $request): JsonResponse
    {
        return $this->featureResponse($request, 'knowledge');
    }

    public function logs(IndexAiRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);
        $paginator = $this->analyticsService->paginateLogs($request->filters());

        return ApiResponse::success([
            'logs' => [
                'items' => AiUsageLogResource::collection($paginator->items())->resolve(),
                'meta' => $this->meta($paginator),
            ],
        ]);
    }

    public function showLog(string $log): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);

        return ApiResponse::success([
            'log' => new AiUsageLogResource($this->analyticsService->findLog($log)),
        ]);
    }

    private function featureResponse(AiFeatureRequest $request, string $method): JsonResponse
    {
        $this->authorize('chat', AiProvider::class);
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->assistantService->{$method}($request->validated(), $actor);

        return ApiResponse::success(['result' => $result]);
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @return array<string, int>
     */
    private function meta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
