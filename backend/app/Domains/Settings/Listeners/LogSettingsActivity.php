<?php

namespace App\Domains\Settings\Listeners;

use App\Domains\Settings\Events\ConfigurationChanged;
use App\Domains\Settings\Events\FolderCreated;
use App\Domains\Settings\Events\FolderDeleted;
use App\Domains\Settings\Events\MediaDeleted;
use App\Domains\Settings\Events\MediaUploaded;
use App\Domains\Settings\Events\SettingsUpdated;
use App\Shared\Helpers\ActivityHelper;

class LogSettingsActivity
{
    public function handleSettingsUpdated(SettingsUpdated $event): void
    {
        ActivityHelper::log('Setting Updated', $event->actor, [
            'event' => 'settings_updated',
            'group' => $event->group,
            'keys' => $event->keys,
        ]);
    }

    public function handleConfigurationChanged(ConfigurationChanged $event): void
    {
        ActivityHelper::log('Configuration Changed', $event->actor, [
            'event' => 'configuration_changed',
            'group' => $event->group,
            'keys' => $event->keys,
        ]);
    }

    public function handleMediaUploaded(MediaUploaded $event): void
    {
        ActivityHelper::log('Media Uploaded', $event->actor, [
            'event' => 'media_uploaded',
            'media_uuid' => $event->media->uuid,
            'original_name' => $event->media->original_name,
        ]);
    }

    public function handleMediaDeleted(MediaDeleted $event): void
    {
        ActivityHelper::log('Media Deleted', $event->actor, [
            'event' => 'media_deleted',
            'media_uuid' => $event->media->uuid,
            'original_name' => $event->media->original_name,
        ]);
    }

    public function handleFolderCreated(FolderCreated $event): void
    {
        ActivityHelper::log('Folder Created', $event->actor, [
            'event' => 'folder_created',
            'folder_uuid' => $event->folder->uuid,
            'name' => $event->folder->name,
        ]);
    }

    public function handleFolderDeleted(FolderDeleted $event): void
    {
        ActivityHelper::log('Folder Deleted', $event->actor, [
            'event' => 'folder_deleted',
            'folder_uuid' => $event->folder->uuid,
            'name' => $event->folder->name,
        ]);
    }
}
