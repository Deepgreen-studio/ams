<?php

namespace App\Domains\Settings\Listeners;

use App\Domains\Settings\Events\ConfigurationChanged;
use App\Domains\Settings\Events\FolderCreated;
use App\Domains\Settings\Events\FolderDeleted;
use App\Domains\Settings\Events\MediaDeleted;
use App\Domains\Settings\Events\MediaUploaded;
use App\Domains\Settings\Events\SettingsUpdated;

/**
 * Architecture stub for future notification channel delivery.
 */
class PrepareSettingsNotifications
{
    public function handleSettingsUpdated(SettingsUpdated $event): void {}

    public function handleConfigurationChanged(ConfigurationChanged $event): void {}

    public function handleMediaUploaded(MediaUploaded $event): void {}

    public function handleMediaDeleted(MediaDeleted $event): void {}

    public function handleFolderCreated(FolderCreated $event): void {}

    public function handleFolderDeleted(FolderDeleted $event): void {}
}
