# Database

## Engines

- MySQL 8+
- Redis for cache, queue, and session (configurable)

## Current Tables

- Laravel defaults: users, password_reset_tokens, sessions, jobs, cache
- Sanctum: personal_access_tokens
- Spatie Permission: roles, permissions, pivots
- Spatie Activitylog: activity_log
- Auth extensions on users: is_active, last_login_at, last_login_ip, soft deletes
- Enterprise user fields: uuid, first_name, last_name, full_name, phone, avatar, gender, date_of_birth, timezone, language, status, created_by, updated_by
- Architecture-ready: user_login_histories
- RBAC extensions: roles uuid/display_name/description/is_system/soft deletes, permission_groups, permissions module/group metadata
- Companies & organization: companies, departments, teams, company_locations, company_user (membership pivot)
- System settings & media: setting_groups, system_settings, file_folders, media_files, configuration_logs
- Audit & monitoring: Spatie activity_log, audit_logs, user_login_histories (enhanced), api_logs, system_events, error_logs

Schema dumps and backups belong in:

- `backend/database/schema`
- `backend/database/backup`
