<?php

namespace App\Domains\Support\Controllers;

use App\Domains\Support\Requests\StoreKnowledgeArticleRequest;
use App\Domains\Support\Requests\StoreKnowledgeCategoryRequest;
use App\Domains\Support\Requests\StoreKnowledgeFeedbackRequest;
use App\Domains\Support\Requests\StoreKnowledgeTagRequest;
use App\Domains\Support\Requests\UpdateKnowledgeArticleRequest;
use App\Domains\Support\Requests\UpdateKnowledgeCategoryRequest;
use App\Domains\Support\Requests\UpdateKnowledgeTagRequest;
use App\Domains\Support\Resources\KnowledgeArticleResource;
use App\Domains\Support\Resources\KnowledgeArticleSummaryResource;
use App\Domains\Support\Resources\KnowledgeArticleVersionResource;
use App\Domains\Support\Resources\KnowledgeCategoryResource;
use App\Domains\Support\Resources\KnowledgeTagResource;
use App\Domains\Support\Services\KnowledgeBaseService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeBaseController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly KnowledgeBaseService $knowledgeBaseService,
    ) {}

    public function dashboard(): JsonResponse
    {
        $data = $this->knowledgeBaseService->dashboard();

        return ApiResponse::success([
            'statistics' => $data['statistics'],
            'types' => $data['types'],
            'featured' => KnowledgeArticleSummaryResource::collection($data['featured'])->resolve(),
            'latest' => KnowledgeArticleSummaryResource::collection($data['latest'])->resolve(),
            'popular' => KnowledgeArticleSummaryResource::collection($data['popular'])->resolve(),
            'categories' => KnowledgeCategoryResource::collection($data['categories'])->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->knowledgeBaseService->listArticles($request->query());

        return ApiResponse::success([
            'articles' => [
                'items' => KnowledgeArticleSummaryResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function show(string $article): JsonResponse
    {
        $model = $this->knowledgeBaseService->showArticle($article, trackView: true);

        return ApiResponse::success([
            'article' => new KnowledgeArticleResource($model),
            'related' => KnowledgeArticleSummaryResource::collection(
                $this->knowledgeBaseService->relatedArticles($article)
            )->resolve(),
        ]);
    }

    public function store(StoreKnowledgeArticleRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $article = $this->knowledgeBaseService->createArticle($request->validated(), $actor);

        return ApiResponse::success([
            'article' => new KnowledgeArticleResource($article),
        ], 'Knowledge article created successfully.', 201);
    }

    public function update(UpdateKnowledgeArticleRequest $request, string $article): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->updateArticle($article, $request->validated(), $actor);

        return ApiResponse::success([
            'article' => new KnowledgeArticleResource($updated),
        ], 'Knowledge article updated successfully.');
    }

    public function publish(Request $request, string $article): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->publishArticle($article, $actor);

        return ApiResponse::success([
            'article' => new KnowledgeArticleResource($updated),
        ], 'Knowledge article published successfully.');
    }

    public function archive(Request $request, string $article): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->archiveArticle($article, $actor);

        return ApiResponse::success([
            'article' => new KnowledgeArticleResource($updated),
        ], 'Knowledge article archived successfully.');
    }

    public function destroy(Request $request, string $article): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->knowledgeBaseService->deleteArticle($article, $actor);

        return ApiResponse::success(null, 'Knowledge article deleted successfully.');
    }

    public function linkCms(Request $request, string $article): JsonResponse
    {
        $request->validate([
            'content_id' => ['required', 'string'],
            'sync' => ['nullable', 'boolean'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->linkCmsContent(
            $article,
            (string) $request->input('content_id'),
            $actor,
            (bool) $request->boolean('sync', true)
        );

        return ApiResponse::success([
            'article' => new KnowledgeArticleResource($updated),
        ], 'Article linked to CMS content.');
    }

    public function unlinkCms(Request $request, string $article): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->unlinkCmsContent($article, $actor);

        return ApiResponse::success([
            'article' => new KnowledgeArticleResource($updated),
        ], 'CMS link removed.');
    }

    public function versions(string $article): JsonResponse
    {
        $versions = $this->knowledgeBaseService->versions($article);

        return ApiResponse::success([
            'versions' => KnowledgeArticleVersionResource::collection($versions)->resolve(),
        ]);
    }

    public function restoreVersion(Request $request, string $article, string $version): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->restoreVersion($article, $version, $actor);

        return ApiResponse::success([
            'article' => new KnowledgeArticleResource($updated),
        ], 'Article restored from version history.');
    }

    public function feedback(StoreKnowledgeFeedbackRequest $request, string $article): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->submitFeedback(
            $article,
            $actor,
            (bool) $request->boolean('is_helpful'),
            $request->validated('comment'),
            $request->ip()
        );

        return ApiResponse::success([
            'article' => [
                'uuid' => $updated->uuid,
                'helpful_count' => $updated->helpful_count,
                'not_helpful_count' => $updated->not_helpful_count,
            ],
        ], 'Thank you for your feedback.');
    }

    public function categories(Request $request): JsonResponse
    {
        $tree = $request->boolean('tree', true);
        $items = $this->knowledgeBaseService->listCategories($tree);

        return ApiResponse::success([
            'categories' => KnowledgeCategoryResource::collection($items)->resolve(),
        ]);
    }

    public function storeCategory(StoreKnowledgeCategoryRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $category = $this->knowledgeBaseService->createCategory($request->validated(), $actor);

        return ApiResponse::success([
            'category' => new KnowledgeCategoryResource($category),
        ], 'Category created successfully.', 201);
    }

    public function updateCategory(UpdateKnowledgeCategoryRequest $request, string $category): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->updateCategory($category, $request->validated(), $actor);

        return ApiResponse::success([
            'category' => new KnowledgeCategoryResource($updated),
        ], 'Category updated successfully.');
    }

    public function destroyCategory(string $category): JsonResponse
    {
        $this->knowledgeBaseService->deleteCategory($category);

        return ApiResponse::success(null, 'Category deleted successfully.');
    }

    public function tags(): JsonResponse
    {
        return ApiResponse::success([
            'tags' => KnowledgeTagResource::collection($this->knowledgeBaseService->listTags())->resolve(),
        ]);
    }

    public function storeTag(StoreKnowledgeTagRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $tag = $this->knowledgeBaseService->createTag($request->validated(), $actor);

        return ApiResponse::success([
            'tag' => new KnowledgeTagResource($tag),
        ], 'Tag created successfully.', 201);
    }

    public function updateTag(UpdateKnowledgeTagRequest $request, string $tag): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->knowledgeBaseService->updateTag($tag, $request->validated(), $actor);

        return ApiResponse::success([
            'tag' => new KnowledgeTagResource($updated),
        ], 'Tag updated successfully.');
    }

    public function destroyTag(string $tag): JsonResponse
    {
        $this->knowledgeBaseService->deleteTag($tag);

        return ApiResponse::success(null, 'Tag deleted successfully.');
    }
}
