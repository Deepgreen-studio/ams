# Settings

See detailed module documentation:

**`docs/modules/Settings.md`**

## Summary

- Grouped system settings (general, email, storage, security, API, queue, cache, localization, notifications)
- Encrypted secrets (SMTP password) with masked API responses
- Media library + nested folders
- Shared services: CacheManager, QueueManager, NotificationManager
- Helpers: ActivityHelper, AuditHelper

## Paths

- Backend: `backend/app/Domains/Settings/` + `backend/app/Shared/`
- Frontend: `frontend/src/modules/settings/`
- Tests: `backend/tests/Feature/Settings/SettingsManagementTest.php`
