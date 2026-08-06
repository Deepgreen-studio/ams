<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Events\ContentVersionRestored;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentVersion;
use App\Domains\Content\Repositories\ContentCategoryRepository;
use App\Domains\Content\Repositories\ContentRepository;
use App\Domains\Content\Repositories\ContentStatusRepository;
use App\Domains\Content\Repositories\ContentTagRepository;
use App\Domains\Content\Repositories\ContentVersionRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ContentVersionService
{
    /**
     * @var list<string>
     */
    protected array $snapshotFields = [
        'title',
        'slug',
        'summary',
        'excerpt',
        'body',
        'body_format',
        'editor_json',
        'featured_image',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_type',
        'schema_json',
        'metadata',
        'is_featured',
        'sort_order',
        'published_at',
    ];

    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentVersionRepository $versionRepository,
        private readonly ContentStatusRepository $contentStatusRepository,
        private readonly ContentCategoryRepository $contentCategoryRepository,
        private readonly ContentTagRepository $contentTagRepository
    ) {}

    /**
     * @return Collection<int, ContentVersion>
     */
    public function list(string $contentIdentifier): Collection
    {
        $content = $this->contentRepository->findByIdentifierOrFail($contentIdentifier);

        return $this->versionRepository->versionsForContent($content->id);
    }

    public function show(string $contentIdentifier, string $versionIdentifier): ContentVersion
    {
        $content = $this->contentRepository->findByIdentifierOrFail($contentIdentifier);

        return $this->versionRepository->findForContent($content->id, $versionIdentifier);
    }

    /**
     * @return array{from: ContentVersion, to: ContentVersion, comparison: array{changes: array<string, array{from: mixed, to: mixed}>, changed_fields: list<string>}}
     */
    public function compare(string $contentIdentifier, string $fromIdentifier, string $toIdentifier): array
    {
        $content = $this->contentRepository->findByIdentifierOrFail($contentIdentifier);
        $from = $this->versionRepository->findForContent($content->id, $fromIdentifier);
        $to = $this->versionRepository->findForContent($content->id, $toIdentifier);

        if ($from->id === $to->id) {
            throw new ApiException('Select two different versions to compare.', 422);
        }

        $fromSnapshot = is_array($from->snapshot) ? $from->snapshot : [];
        $toSnapshot = is_array($to->snapshot) ? $to->snapshot : [];
        $keys = array_values(array_unique(array_merge(array_keys($fromSnapshot), array_keys($toSnapshot))));
        $changes = [];

        foreach ($keys as $key) {
            $fromValue = $fromSnapshot[$key] ?? null;
            $toValue = $toSnapshot[$key] ?? null;
            if ($this->normalizeComparable($fromValue) !== $this->normalizeComparable($toValue)) {
                $changes[$key] = [
                    'from' => $fromValue,
                    'to' => $toValue,
                ];
            }
        }

        if (($from->status ?? null) !== ($to->status ?? null)) {
            $changes['status'] = [
                'from' => $from->status,
                'to' => $to->status,
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
            'comparison' => [
                'changes' => $changes,
                'changed_fields' => array_keys($changes),
            ],
        ];
    }

    public function restore(
        string $contentIdentifier,
        string $versionIdentifier,
        User $actor,
        ?string $reason = null
    ): Content {
        return DB::transaction(function () use ($contentIdentifier, $versionIdentifier, $actor, $reason): Content {
            $content = $this->contentRepository->findByIdentifierOrFail($contentIdentifier)
                ->load(['categories:id,uuid', 'tags:id,uuid,name', 'status', 'type']);
            $history = $this->versionRepository->findForContent($content->id, $versionIdentifier);
            $snapshot = is_array($history->snapshot) ? $history->snapshot : [];

            $payload = [];
            foreach ($this->snapshotFields as $field) {
                if (array_key_exists($field, $snapshot)) {
                    $payload[$field] = $snapshot[$field];
                }
            }

            if (! empty($snapshot['content_type_id'])) {
                $payload['content_type_id'] = (int) $snapshot['content_type_id'];
            }

            if (! empty($snapshot['status_slug'])) {
                $status = $this->contentStatusRepository->findBySlugOrFail((string) $snapshot['status_slug']);
                $payload['content_status_id'] = $status->id;
            } elseif (! empty($snapshot['content_status_id'])) {
                $payload['content_status_id'] = (int) $snapshot['content_status_id'];
            }

            $payload['version'] = ((int) $content->version) + 1;
            $payload['updated_by'] = $actor->id;

            $updated = $this->contentRepository->updateContent($content, $payload);

            $categoryIds = $this->contentCategoryRepository->resolveIds(
                is_array($snapshot['category_uuids'] ?? null) ? $snapshot['category_uuids'] : []
            );
            $updated = $this->contentRepository->syncCategories($updated, $categoryIds);

            $tagIdentifiers = is_array($snapshot['tag_names'] ?? null) ? $snapshot['tag_names'] : [];
            if ($tagIdentifiers === [] && is_array($snapshot['tag_uuids'] ?? null)) {
                $tagIdentifiers = $snapshot['tag_uuids'];
            }
            $tagIds = $this->contentTagRepository->resolveIds($tagIdentifiers, $actor->id);
            $updated = $this->contentRepository->syncTags($updated, $tagIds);

            $updated = $updated->load(['categories:id,uuid,name,slug', 'tags:id,uuid,name,slug', 'status', 'type']);

            $this->recordVersion(
                $updated,
                $reason ?: 'Restored from version '.$history->version,
                $actor
            );

            event(new ContentVersionRestored($updated, $actor, $history));

            return $updated;
        });
    }

    public function ensureBaselineVersion(Content $content, User $actor): void
    {
        $existing = $this->versionRepository->versionsForContent($content->id);
        if ($existing->isNotEmpty()) {
            return;
        }

        $content->loadMissing(['status', 'type', 'categories', 'tags']);
        $versionNumber = max(1, (int) $content->version);

        if ((int) $content->version !== $versionNumber) {
            $this->contentRepository->updateContent($content, [
                'version' => $versionNumber,
                'updated_by' => $actor->id,
            ]);
            $content->version = $versionNumber;
        }

        $this->versionRepository->createVersion([
            'content_id' => $content->id,
            'version' => $versionNumber,
            'status' => $content->status?->slug ?? 'draft',
            'snapshot' => $this->buildSnapshot($content),
            'reason' => 'Baseline snapshot',
            'created_by' => $actor->id,
            'created_at' => now(),
        ]);
    }

    public function recordVersion(Content $content, string $reason, User $actor): ContentVersion
    {
        $content->loadMissing(['status', 'type', 'categories:id,uuid,name,slug', 'tags:id,uuid,name,slug']);

        $versionNumber = max(
            (int) $content->version,
            $this->versionRepository->nextVersionNumber($content->id)
        );

        if ((int) $content->version !== $versionNumber) {
            $this->contentRepository->updateContent($content, [
                'version' => $versionNumber,
                'updated_by' => $actor->id,
            ]);
            $content->version = $versionNumber;
        }

        return $this->versionRepository->createVersion([
            'content_id' => $content->id,
            'version' => $versionNumber,
            'status' => $content->status?->slug ?? 'draft',
            'snapshot' => $this->buildSnapshot($content),
            'reason' => $reason,
            'created_by' => $actor->id,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSnapshot(Content $content): array
    {
        $content->loadMissing(['status', 'type', 'categories:id,uuid,name,slug', 'tags:id,uuid,name,slug']);

        $snapshot = [];
        foreach ($this->snapshotFields as $field) {
            $value = $content->{$field};
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }
            if ($value instanceof \Illuminate\Support\Carbon) {
                $value = $value->toIso8601String();
            }
            $snapshot[$field] = $value;
        }

        $snapshot['content_type_id'] = $content->content_type_id;
        $snapshot['content_type_slug'] = $content->type?->slug;
        $snapshot['content_status_id'] = $content->content_status_id;
        $snapshot['status_slug'] = $content->status?->slug;
        $snapshot['content_category_id'] = $content->content_category_id;
        $snapshot['category_uuids'] = $content->categories->pluck('uuid')->values()->all();
        $snapshot['category_names'] = $content->categories->pluck('name')->values()->all();
        $snapshot['tag_uuids'] = $content->tags->pluck('uuid')->values()->all();
        $snapshot['tag_names'] = $content->tags->pluck('name')->values()->all();

        return $snapshot;
    }

    protected function normalizeComparable(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
