# Release Notes

## Phase 1.8 — Foundation Review & Production Readiness

- Full architecture / API / security / performance review of Phase 1
- CORS defaults hardened (no `*` + credentials fallback)
- Company controller repository leak removed (service-only authorization lookups)
- Documentation suite completed for Phase 1 handoff
- README, env examples, docker notes, deployment checklist updated
- Pint formatting + full feature regression suite executed

## Phase 1.7 — Audit Trail, Activity Log & System Monitoring

- Audit domain with activity (Spatie), audit trails, login history, API logs, system events, error logs
- API request logging middleware + exception persistence
- Frontend audit module with export (CSV)
- Permissions: `audit.view|export|manage`

## Phase 1.6 — System Settings & Shared Services

- Grouped settings, media library, folders, configuration logs
- Shared Cache/Queue/Notification managers + Audit/Activity helpers
- Frontend settings module (tabbed)

## Phase 1.5 — Company & Organization Management

- Companies, departments, teams, locations, branding/logo
- Soft delete/restore, multi-company pivot architecture

## Phase 1.4 — Roles & Permissions

- Spatie RBAC with custom models, permission groups, matrix UI
- Default enterprise roles seeded

## Phase 1.3 — User Management

- Enterprise user CRUD, profile/avatar, soft delete/restore, activity summary

## Phase 1.2 — Authentication & Security

- Complete Authentication domain (DDD)
- Sanctum cookie + API token hybrid login response
- Login, logout, logout-all devices, me, refresh session
- Forgot/reset password, change password, email verification
- Rate limiting for login and password flows

## Phase 1.1 + Monorepo Restructure

- Converted project to `backend/` + `frontend/` monorepo
- Added docs, docker-compose, scripts, LICENSE
