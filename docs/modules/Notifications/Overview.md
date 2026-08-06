# Notifications Module

> **Status:** Superseded for Phase 8.  
> Use the Phase 8 documentation set below. This file remains as a redirect to avoid stale Phase 6.7 guidance.

## Canonical docs

- [Notification Documentation](../Notification-Automation/Notification-Documentation.md)
- [Notification Center Overview](../../notifications/Overview.md)
- [Templates](../../notifications/Templates.md)
- [Administrator Guide](../Notification-Automation/Administrator-Guide.md)
- [Phase 8.8 Production Readiness](../Notification-Automation/reviews/Production-Readiness-Report.md)

## Quick facts (Phase 8)

| Item | Value |
|------|-------|
| Domain | `backend/app/Domains/Notifications` |
| API | `/api/v1/notifications` |
| Tables | `notifications`, `notification_logs`, templates/versions/approvals, channels, preferences, `database_notifications` |
| Channels active | Email, In-App |
| Click tracking | Schema ready (`clicked_at`) — API/UI pending |
