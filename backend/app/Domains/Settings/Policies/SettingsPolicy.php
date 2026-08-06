<?php

namespace App\Domains\Settings\Policies;

use App\Domains\Settings\Enums\SettingPermission;
use App\Domains\Settings\Models\FileFolder;
use App\Domains\Settings\Models\MediaFile;
use App\Domains\Settings\Models\SystemSetting;
use App\Models\User;

class SettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(SettingPermission::VIEW);
    }

    public function view(User $user, ?SystemSetting $setting = null): bool
    {
        return $user->can(SettingPermission::VIEW);
    }

    public function update(User $user): bool
    {
        return $user->can(SettingPermission::UPDATE);
    }

    public function manageMedia(User $user): bool
    {
        return $user->can(SettingPermission::MANAGE) || $user->can(SettingPermission::UPDATE);
    }

    public function viewMedia(User $user): bool
    {
        return $user->can(SettingPermission::VIEW) || $user->can(SettingPermission::MANAGE);
    }

    public function deleteMedia(User $user, ?MediaFile $media = null): bool
    {
        return $this->manageMedia($user);
    }

    public function manageFolders(User $user): bool
    {
        return $this->manageMedia($user);
    }

    public function viewFolders(User $user): bool
    {
        return $this->viewMedia($user);
    }

    public function deleteFolder(User $user, ?FileFolder $folder = null): bool
    {
        return $this->manageFolders($user);
    }
}
