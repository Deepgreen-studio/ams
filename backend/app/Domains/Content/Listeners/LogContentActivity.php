<?php

namespace App\Domains\Content\Listeners;

use App\Domains\Content\Events\ContentCreated;
use App\Domains\Content\Events\ContentDeleted;
use App\Domains\Content\Events\ContentPublished;
use App\Domains\Content\Events\ContentRestored;
use App\Domains\Content\Events\ContentUnpublished;
use App\Domains\Content\Events\ContentUpdated;
use App\Domains\Content\Events\ContentVersionRestored;
use App\Domains\Content\Events\ContentWorkflowTransitioned;
use App\Domains\Content\Events\MediaLibraryDeleted;
use App\Domains\Content\Events\MediaLibraryReplaced;
use App\Domains\Content\Events\MediaLibraryUploaded;

class LogContentActivity
{
    public function handleContentCreated(ContentCreated $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->content)
            ->withProperties([
                'event' => 'content_created',
                'title' => $event->content->title,
                'slug' => $event->content->slug,
                'type' => $event->content->type?->slug,
                'status' => $event->content->status?->slug,
                'version' => $event->content->version,
            ])
            ->log('Content created');
    }

    public function handleContentUpdated(ContentUpdated $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->content)
            ->withProperties([
                'event' => 'content_updated',
                'title' => $event->content->title,
                'slug' => $event->content->slug,
                'status' => $event->content->status?->slug,
                'version' => $event->content->version,
            ])
            ->log('Content updated');
    }

    public function handleContentDeleted(ContentDeleted $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->content)
            ->withProperties([
                'event' => 'content_deleted',
                'title' => $event->content->title,
                'slug' => $event->content->slug,
            ])
            ->log('Content deleted');
    }

    public function handleContentRestored(ContentRestored $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->content)
            ->withProperties([
                'event' => 'content_restored',
                'title' => $event->content->title,
                'slug' => $event->content->slug,
            ])
            ->log('Content restored');
    }

    public function handleContentPublished(ContentPublished $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->content)
            ->withProperties([
                'event' => 'content_published',
                'title' => $event->content->title,
                'slug' => $event->content->slug,
                'published_at' => optional($event->content->published_at)?->toIso8601String(),
                'version' => $event->content->version,
            ])
            ->log('Content published');
    }

    public function handleContentUnpublished(ContentUnpublished $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->content)
            ->withProperties([
                'event' => 'content_unpublished',
                'title' => $event->content->title,
                'slug' => $event->content->slug,
                'version' => $event->content->version,
            ])
            ->log('Content unpublished');
    }

    public function handleContentVersionRestored(ContentVersionRestored $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->content)
            ->withProperties([
                'event' => 'content_version_restored',
                'title' => $event->content->title,
                'slug' => $event->content->slug,
                'restored_from_version' => $event->version->version,
                'version' => $event->content->version,
            ])
            ->log('Content version restored');
    }

    public function handleContentWorkflowTransitioned(ContentWorkflowTransitioned $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->content)
            ->withProperties([
                'event' => 'content_workflow_'.$event->history->action,
                'title' => $event->content->title,
                'from_status' => $event->history->from_status,
                'to_status' => $event->history->to_status,
                'approval_level' => $event->history->approval_level,
                'comments' => $event->history->comments,
                'version' => $event->content->version,
            ])
            ->log('Content workflow: '.$event->history->action);
    }

    public function handleMediaLibraryUploaded(MediaLibraryUploaded $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->media)
            ->withProperties([
                'event' => 'media_uploaded',
                'name' => $event->media->original_name,
                'type' => $event->media->type,
                'version' => $event->media->version,
            ])
            ->log('CMS media uploaded');
    }

    public function handleMediaLibraryReplaced(MediaLibraryReplaced $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->media)
            ->withProperties([
                'event' => 'media_replaced',
                'name' => $event->media->original_name,
                'version' => $event->media->version,
                'previous_version' => $event->previous->version,
            ])
            ->log('CMS media replaced');
    }

    public function handleMediaLibraryDeleted(MediaLibraryDeleted $event): void
    {
        activity('content')
            ->causedBy($event->actor)
            ->performedOn($event->media)
            ->withProperties([
                'event' => 'media_deleted',
                'name' => $event->media->original_name,
                'version' => $event->media->version,
            ])
            ->log('CMS media deleted');
    }
}
