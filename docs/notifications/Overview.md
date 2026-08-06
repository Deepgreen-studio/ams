# Notifications Domain — Phase 8.1 Foundation

## Overview

Centralized Enterprise Notification Center for AMS. All future modules must use this domain for sending, tracking, and managing notifications.

## Responsibilities

- Persist multi-channel notification records
- Manage templates, channels, preferences, and delivery logs
- Expose Notification Dashboard, Center, History, Unread, and Preferences APIs
- Provide repository/service foundation for later automation

## Channels

| Channel | Status |
|---------|--------|
| Email | Implemented |
| In-App | Implemented |
| Push | Future |
| SMS | Future |
| WhatsApp | Future |
| Slack | Future |
| Microsoft Teams | Future |
| Webhook | Future (registry ready) |

## Tables

- `notifications` — enterprise notification records
- `database_notifications` — Laravel database-channel storage
- `notification_templates`
- `notification_channels`
- `notification_logs` (renamed from `notification_delivery_logs`)
- `notification_preferences`

## API (prefix `/api/v1/notifications`)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/dashboard` | Dashboard statistics |
| GET | `/center` | Center summary |
| GET | `/` | History (search/filter/paginate) |
| GET | `/unread` | Unread inbox |
| GET | `/unread-count` | Badge count |
| POST | `/{uuid}/read` | Mark read |
| POST | `/read-all` | Mark all read |
| GET/PUT | `/preferences` | User preferences |
| GET/PUT | `/channels` | Channel registry |
| CRUD | `/templates` | Templates |
| GET | `/logs` | Delivery logs |

## Permissions

- `notifications.view`
- `notifications.create`
- `notifications.update`
- `notifications.delete`

## Out of scope (Phase 8.1)

Automation rules, scheduled campaign runners, and future channel drivers (SMS, Push, WhatsApp, Slack, Teams, Webhook delivery).

## Related

- Phase 8.2 Template Management: [Templates.md](./Templates.md)
