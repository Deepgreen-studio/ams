# Notification Templates — Phase 8.2

## Overview

Enterprise template management for AMS notifications: multi-channel content, localization, preview, test send, version history, and approval workflow.

## Supported channels

Email, In-App, Push, SMS, WhatsApp, Slack, Microsoft Teams, Webhook

## Features

- TipTap rich text editor for HTML channels
- Dynamic `{{placeholders}}`
- Locale-aware templates with English fallback
- Immutable version history + compare + restore
- Draft → Review → Approved → Published workflow
- Preview and test send

## Tables

- `notification_templates` (+ `locale`, `workflow_status`, `current_version`, `published_at`)
- `notification_template_versions`
- `notification_template_approvals`

## Key APIs

| Method | Path |
|--------|------|
| GET/POST | `/api/v1/notifications/templates` |
| GET/PUT/DELETE | `/api/v1/notifications/templates/{uuid}` |
| POST | `/templates/{uuid}/preview` |
| POST | `/templates/{uuid}/test-send` |
| POST | `/templates/{uuid}/submit` |
| POST | `/templates/{uuid}/publish` |
| GET | `/templates/{uuid}/versions` |
| GET | `/templates/{uuid}/versions/compare` |
| POST | `/templates/{uuid}/versions/{version}/restore` |
| GET | `/templates/approvals` |
| POST | `/templates/approvals/{uuid}/approve\|reject` |

## Permissions

- `notifications.view|create|update|delete`
- `notifications.approve`
- `notifications.publish`

## Frontend routes

- Template Manager
- Create / Edit editor
- Preview & Test Send
- Version History / Compare
- Approval Queue
