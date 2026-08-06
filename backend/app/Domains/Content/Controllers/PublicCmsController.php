<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Resources\ContentCategoryResource;
use App\Domains\Content\Resources\ContentTagResource;
use App\Domains\Content\Resources\HeadlessContentCollection;
use App\Domains\Content\Resources\HeadlessContentResource;
use App\Domains\Content\Services\HeadlessContentService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCmsController
{
    public function __construct(
        private readonly HeadlessContentService $headlessContentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $contents = $this->headlessContentService->listPublished($this->filters($request));

        return ApiResponse::success([
            'contents' => (new HeadlessContentCollection($contents))->resolve(),
        ]);
    }

    public function show(Request $request, string $content): JsonResponse
    {
        $item = $this->headlessContentService->showPublished(
            $content,
            $request->query('type'),
            trackView: ! $request->boolean('preview')
        );

        return ApiResponse::success([
            'content' => new HeadlessContentResource($item),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $filters['q'] = $request->query('q', $request->query('search'));

        $contents = $this->headlessContentService->searchPublished($filters);

        return ApiResponse::success([
            'contents' => (new HeadlessContentCollection($contents))->resolve(),
        ]);
    }

    public function featured(Request $request): JsonResponse
    {
        $contents = $this->headlessContentService->featured($this->filters($request));

        return ApiResponse::success([
            'contents' => (new HeadlessContentCollection($contents))->resolve(),
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $contents = $this->headlessContentService->latest($this->filters($request));

        return ApiResponse::success([
            'contents' => (new HeadlessContentCollection($contents))->resolve(),
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $contents = $this->headlessContentService->popular($this->filters($request));

        return ApiResponse::success([
            'contents' => (new HeadlessContentCollection($contents))->resolve(),
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = $this->headlessContentService->categories();

        return ApiResponse::success([
            'categories' => ContentCategoryResource::collection($categories)->resolve(),
        ]);
    }

    public function category(string $category): JsonResponse
    {
        $item = $this->headlessContentService->category($category);

        return ApiResponse::success([
            'category' => new ContentCategoryResource($item),
        ]);
    }

    public function categoryContents(Request $request, string $category): JsonResponse
    {
        $contents = $this->headlessContentService->categoryContents(
            $category,
            $this->filters($request)
        );

        return ApiResponse::success([
            'contents' => (new HeadlessContentCollection($contents))->resolve(),
        ]);
    }

    public function tags(Request $request): JsonResponse
    {
        $tags = $this->headlessContentService->tags($this->filters($request));

        return ApiResponse::success([
            'tags' => [
                'items' => ContentTagResource::collection($tags->items())->resolve(),
                'meta' => [
                    'current_page' => $tags->currentPage(),
                    'per_page' => $tags->perPage(),
                    'total' => $tags->total(),
                    'last_page' => $tags->lastPage(),
                ],
            ],
        ]);
    }

    public function tag(string $tag): JsonResponse
    {
        $item = $this->headlessContentService->tag($tag);

        return ApiResponse::success([
            'tag' => new ContentTagResource($item),
        ]);
    }

    public function tagContents(Request $request, string $tag): JsonResponse
    {
        $contents = $this->headlessContentService->tagContents(
            $tag,
            $this->filters($request)
        );

        return ApiResponse::success([
            'contents' => (new HeadlessContentCollection($contents))->resolve(),
        ]);
    }

    public function seo(Request $request, string $content): JsonResponse
    {
        $item = $this->headlessContentService->findPublished($content, $request->query('type'));

        return ApiResponse::success([
            'seo' => $this->headlessContentService->seoPayload($item),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(Request $request): array
    {
        return $request->only([
            'search',
            'q',
            'type',
            'category',
            'tag',
            'content_type_id',
            'content_category_id',
            'is_featured',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'include_body',
            'include_seo',
        ]);
    }
}
