# Notification & Automation — Documentation Index

**Milestone:** Phase 8.8 — Notification & Automation Review  
**Date:** 2026-08-05  
**Scope:** Notification Center, Templates, Channels, Automation Rules, Workflow Engine, Scheduler, AI Integration, Platform Analytics

## Guides

| Document | Audience |
|----------|----------|
| [Notification Documentation](./Notification-Documentation.md) | Product + Ops |
| [Automation Documentation](./Automation-Documentation.md) | Product + Ops |
| [Workflow Documentation](./Workflow-Documentation.md) | Product + Ops |
| [AI Integration Guide](./AI-Integration-Guide.md) | Developers |
| [Developer Guide](./Developer-Guide.md) | Engineers |
| [Administrator Guide](./Administrator-Guide.md) | Platform admins |

## Review reports

| Report | Path |
|--------|------|
| Architecture Report | [reviews/Architecture-Report.md](./reviews/Architecture-Report.md) |
| Security Report | [reviews/Security-Report.md](./reviews/Security-Report.md) |
| Performance Report | [reviews/Performance-Report.md](./reviews/Performance-Report.md) |
| Testing Report | [reviews/Testing-Report.md](./reviews/Testing-Report.md) |
| Automation Readiness Report | [reviews/Automation-Readiness-Report.md](./reviews/Automation-Readiness-Report.md) |
| Production Readiness Report | [reviews/Production-Readiness-Report.md](./reviews/Production-Readiness-Report.md) |

## Domain overviews (Phase builds)

- `docs/notifications/Overview.md` · `Templates.md`
- `docs/automation/Overview.md`
- `docs/workflows/Overview.md`
- `docs/scheduler/Overview.md`
- `docs/ai/Overview.md`
- `docs/analytics/Overview.md`

## Test command

```bash
cd backend
php artisan test tests/Feature/Notifications tests/Feature/Automation tests/Feature/Workflows tests/Feature/Scheduler tests/Feature/Ai tests/Feature/Analytics
```

**Result (Phase 8.8):** 36 passed (239 assertions)

## Stop

Phase 8.8 complete. **Do not start Phase 9 without explicit approval.**
