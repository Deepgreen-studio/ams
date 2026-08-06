# Settings Module

## Overview

Enterprise System Settings & Shared Services for AMS Phase 1.6.

Provides the central configuration backbone used by every platform module, plus media library and file manager services.

## Responsibilities

- Global system settings by group (general, email, storage, security, API, queue, cache, localization, notifications)
- Encrypted secret handling (SMTP password) with masked API responses
- Configuration change logs
- Cached settings map with refresh endpoint
- Enterprise media library and nested folder file manager
- Shared managers: Cache, Queue, Notification, Configuration, Audit/Activity helpers

## Folder Structure

```
backend/app/Domains/Settings/
backend/app/Shared/Services/
backend/app/Shared/Helpers/
frontend/src/modules/settings/
```

## Database Tables

- `setting_groups`
- `system_settings`
- `file_folders`
- `media_files`
- `configuration_logs`

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/PUT | `/api/v1/settings` | General settings |
| GET/PUT | `/api/v1/settings/email` | Email / SMTP |
| GET/PUT | `/api/v1/settings/storage` | Storage / uploads |
| GET/PUT | `/api/v1/settings/security` | Security policies |
| GET/PUT | `/api/v1/settings/api` | API behaviour |
| GET/PUT | `/api/v1/settings/queue` | Queue defaults |
| GET | `/api/v1/settings/cache` | Cache status |
| GET | `/api/v1/settings/system-info` | Runtime info |
| POST | `/api/v1/settings/refresh-cache` | Refresh config cache |
| GET/POST/DELETE | `/api/v1/media` | Media library |
| GET/POST/PUT/DELETE | `/api/v1/folders` | File folders |

## Permissions

- `settings.view`
- `settings.update`
- `settings.manage`

## Testing

```bash
php artisan test --filter=SettingsManagementTest
```
