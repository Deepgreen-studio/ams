<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Enums\ContentWorkflowAction;
use App\Domains\Content\Enums\ContentWorkflowLevel;
use App\Domains\Content\Events\ContentPublished;
use App\Domains\Content\Events\ContentWorkflowTransitioned;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentWorkflowHistory;
use App\Domains\Content\Repositories\ContentRepository;
use App\Domains\Content\Repositories\ContentStatusRepository;
use App\Domains\Content\Repositories\ContentWorkflowHistoryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ContentWorkflowService
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentStatusRepository $contentStatusRepository,
        private readonly ContentWorkflowHistoryRepository $historyRepository,
        private readonly ContentVersionService $contentVersionService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function queue(User $actor, array $filters = []): LengthAwarePaginator
    {
        $statuses = $this->queueStatusesFor($actor);
        if ($statuses === []) {
            $filters['status'] = '__none__';
        } else {
            $filters['statuses'] = $statuses;
        }

        return $this->contentRepository->paginateFiltered($filters);
    }

    /**
     * @return Collection<int, ContentWorkflowHistory>
     */
    public function history(string $identifier): Collection
    {
        $content = $this->contentRepository->findByIdentifierOrFail($identifier);

        return $this->historyRepository->forContent($content->id);
    }

    public function submit(string $identifier, User $actor, ?string $comments = null): Content
    {
        $this->assertCan($actor, ContentPermission::SUBMIT);

        return $this->transition(
            $identifier,
            $actor,
            ContentWorkflowAction::Submit,
            ContentWorkflowLevel::Writer,
            [ContentStatusSlug::Draft, ContentStatusSlug::Rejected],
            ContentStatusSlug::PendingReview,
            [
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'current_workflow_level' => ContentWorkflowLevel::Editor->value,
            ],
            $comments,
            'Submitted for review'
        );
    }

    public function review(string $identifier, User $actor, ?string $comments = null): Content
    {
        $this->assertCan($actor, ContentPermission::REVIEW);

        return $this->transition(
            $identifier,
            $actor,
            ContentWorkflowAction::Review,
            ContentWorkflowLevel::Editor,
            [ContentStatusSlug::PendingReview],
            ContentStatusSlug::Reviewed,
            [
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'current_workflow_level' => ContentWorkflowLevel::Manager->value,
            ],
            $comments,
            'Marked as reviewed'
        );
    }

    public function approve(string $identifier, User $actor, ?string $comments = null): Content
    {
        $this->assertCan($actor, ContentPermission::APPROVE);

        return $this->transition(
            $identifier,
            $actor,
            ContentWorkflowAction::Approve,
            ContentWorkflowLevel::Manager,
            [ContentStatusSlug::Reviewed],
            ContentStatusSlug::Approved,
            [
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'current_workflow_level' => ContentWorkflowLevel::Administrator->value,
            ],
            $comments,
            'Approved for publishing'
        );
    }

    public function reject(string $identifier, User $actor, string $comments): Content
    {
        if (
            ! $actor->can(ContentPermission::REVIEW)
            && ! $actor->can(ContentPermission::APPROVE)
            && ! $actor->can(ContentPermission::PUBLISH)
        ) {
            throw new ApiException('You are not allowed to reject content.', 403);
        }

        if (blank($comments)) {
            throw new ApiException('Rejection comments are required.', 422);
        }

        $level = $actor->can(ContentPermission::PUBLISH)
            ? ContentWorkflowLevel::Administrator
            : ($actor->can(ContentPermission::APPROVE)
                ? ContentWorkflowLevel::Manager
                : ContentWorkflowLevel::Editor);

        return $this->transition(
            $identifier,
            $actor,
            ContentWorkflowAction::Reject,
            $level,
            [ContentStatusSlug::PendingReview, ContentStatusSlug::Reviewed, ContentStatusSlug::Approved],
            ContentStatusSlug::Rejected,
            [
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'current_workflow_level' => ContentWorkflowLevel::Writer->value,
            ],
            $comments,
            'Content rejected'
        );
    }

    public function publish(string $identifier, User $actor, ?string $comments = null, ?string $publishedAt = null): Content
    {
        $this->assertCan($actor, ContentPermission::PUBLISH);

        return $this->transition(
            $identifier,
            $actor,
            ContentWorkflowAction::Publish,
            ContentWorkflowLevel::Administrator,
            [ContentStatusSlug::Approved],
            ContentStatusSlug::Published,
            [
                'published_by' => $actor->id,
                'published_at' => $publishedAt ?: now(),
                'current_workflow_level' => null,
            ],
            $comments,
            'Published via approval workflow',
            firePublished: true
        );
    }

    public function archive(string $identifier, User $actor, ?string $comments = null): Content
    {
        if (! $actor->can(ContentPermission::UPDATE) && ! $actor->can(ContentPermission::PUBLISH)) {
            throw new ApiException('You are not allowed to archive content.', 403);
        }

        return $this->transition(
            $identifier,
            $actor,
            ContentWorkflowAction::Archive,
            ContentWorkflowLevel::Administrator,
            [ContentStatusSlug::Published],
            ContentStatusSlug::Archived,
            [
                'current_workflow_level' => null,
            ],
            $comments,
            'Content archived'
        );
    }

    public function returnToDraft(string $identifier, User $actor, ?string $comments = null): Content
    {
        if (! $actor->can(ContentPermission::SUBMIT) && ! $actor->can(ContentPermission::UPDATE)) {
            throw new ApiException('You are not allowed to return content to draft.', 403);
        }

        return $this->transition(
            $identifier,
            $actor,
            ContentWorkflowAction::ReturnToDraft,
            ContentWorkflowLevel::Writer,
            [ContentStatusSlug::Rejected, ContentStatusSlug::PendingReview],
            ContentStatusSlug::Draft,
            [
                'current_workflow_level' => ContentWorkflowLevel::Writer->value,
            ],
            $comments,
            'Returned to draft'
        );
    }

    /**
     * @param  list<ContentStatusSlug>  $fromStatuses
     * @param  array<string, mixed>  $extraPayload
     */
    protected function transition(
        string $identifier,
        User $actor,
        ContentWorkflowAction $action,
        ContentWorkflowLevel $level,
        array $fromStatuses,
        ContentStatusSlug $toStatus,
        array $extraPayload,
        ?string $comments,
        string $versionReason,
        bool $firePublished = false
    ): Content {
        return DB::transaction(function () use (
            $identifier,
            $actor,
            $action,
            $level,
            $fromStatuses,
            $toStatus,
            $extraPayload,
            $comments,
            $versionReason,
            $firePublished
        ): Content {
            $content = $this->contentRepository->findByIdentifierOrFail($identifier)
                ->load(['status', 'type', 'categories', 'tags']);

            $currentSlug = $content->status?->slug;
            $allowed = array_map(fn (ContentStatusSlug $status) => $status->value, $fromStatuses);

            if (! in_array($currentSlug, $allowed, true)) {
                throw new ApiException(
                    sprintf(
                        'Cannot %s content from status "%s". Allowed: %s.',
                        $action->value,
                        $currentSlug ?: 'unknown',
                        implode(', ', $allowed)
                    ),
                    422
                );
            }

            $status = $this->contentStatusRepository->findBySlugOrFail($toStatus->value);
            $this->contentVersionService->ensureBaselineVersion($content, $actor);
            $content = $content->fresh(['status', 'type', 'categories', 'tags']) ?? $content;

            $payload = array_merge([
                'content_status_id' => $status->id,
                'updated_by' => $actor->id,
                'version' => ((int) $content->version) + 1,
                'last_workflow_comment' => $comments,
            ], $extraPayload);

            $updated = $this->contentRepository->updateContent($content, $payload);
            $updated = $updated->load(['status', 'type', 'categories', 'tags', 'submitter', 'reviewer', 'approver', 'rejector', 'publisher', 'creator']);

            $this->contentVersionService->recordVersion($updated, $versionReason, $actor);

            $history = $this->historyRepository->createHistory([
                'content_id' => $updated->id,
                'from_status' => $currentSlug,
                'to_status' => $toStatus->value,
                'action' => $action->value,
                'approval_level' => $level->value,
                'acted_by' => $actor->id,
                'comments' => $comments,
                'metadata' => [
                    'version' => $updated->version,
                    'workflow_level' => $level->value,
                ],
                'created_at' => now(),
            ]);

            event(new ContentWorkflowTransitioned($updated, $actor, $history));

            if ($firePublished) {
                event(new ContentPublished($updated, $actor));
            }

            return $updated;
        });
    }

    /**
     * @return list<string>
     */
    protected function queueStatusesFor(User $actor): array
    {
        $statuses = [];

        if ($actor->can(ContentPermission::REVIEW)) {
            $statuses[] = ContentStatusSlug::PendingReview->value;
        }
        if ($actor->can(ContentPermission::APPROVE)) {
            $statuses[] = ContentStatusSlug::Reviewed->value;
        }
        if ($actor->can(ContentPermission::PUBLISH)) {
            $statuses[] = ContentStatusSlug::Approved->value;
        }

        return array_values(array_unique($statuses));
    }

    protected function assertCan(User $actor, string $permission): void
    {
        if (! $actor->can($permission)) {
            throw new ApiException('You are not allowed to perform this workflow action.', 403);
        }
    }
}
