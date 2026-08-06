# Notification Documentation

## Overview

The AMS Notification Center is the enterprise hub for multi-channel messaging. Modules must dispatch through this domain rather than sending ad-hoc mailables.

**Domain:** `backend/app/Domains/Notifications/`  
**Frontend:** `frontend/src/modules/notifications/`  
**API prefix:** `/api/v1/notifications`

## Capabilities

| Capability | Status |
|------------|--------|
| Notification Center (inbox) | Production |
| Unread / History / Preferences | Production |
| Email + In-App delivery | Production |
| Template manager (versions, preview, approve, publish) | Production |
| Channel registry | Production (delivery for future channels reserved) |
| Delivery logs | Production |
| Click tracking columns | Schema ready — **API/UI not wired** |
| Push / SMS / WhatsApp / Slack / Teams / Webhook send | Future |

## Notification Center

Operators and agents use:

- **Center** — summary + recent items
- **Unread** — badge-driven inbox
- **History** — search, filter, paginate
- **Preferences** — per-event channel opt-in/out

Personal endpoints require Sanctum authentication; ownership is enforced in `NotificationService`.

## Templates

Templates support:

- Localized content (event + channel + locale)
- Version history + compare/restore
- Approval queue → publish
- Preview and test send

Permissions: `notifications.view|create|update|approve|publish`

See also: `docs/notifications/Templates.md`

## Channels

| Channel | Delivery |
|---------|----------|
| `email` | Active |
| `in_app` | Active |
| `push`, `sms`, `whatsapp`, `slack`, `teams`, `webhook` | Registry ready |

Admin API: `GET/PUT /api/v1/notifications/channels`  
**Gap:** dedicated Channel admin page is not present in the SPA; API works.

## Database

| Table | Purpose |
|-------|---------|
| `notifications` | Platform notification records (`read_at`, `clicked_at`, `click_count`) |
| `database_notifications` | Laravel database channel store |
| `notification_templates` / `_versions` / `_approvals` | Template lifecycle |
| `notification_channels` | Channel registry + config JSON |
| `notification_logs` | Delivery attempts |
| `notification_preferences` | Per-user preferences |

## Events & activity

CRUD and dispatch flows emit domain events logged via Spatie Activitylog (where configured). Support ticket lifecycle events trigger notification dispatch.

## Known limitations

1. Click rate analytics will remain near zero until `markAsClicked()` is exposed via API and UI.
2. Channel `config` is not encrypted — avoid storing long-lived secrets until encryption is added.
3. `docs/modules/Notifications/Overview.md` is stale (Phase 6.7) — prefer this document and `docs/notifications/*`.

## Permissions

- `notifications.view`
- `notifications.create`
- `notifications.update`
- `notifications.delete`
- `notifications.approve`
- `notifications.publish`
