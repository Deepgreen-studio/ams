# Administrator Guide — Notification & Automation Platform

## What this platform covers

| Area | Sidebar entry | Purpose |
|------|---------------|---------|
| Notifications | Notifications | Inbox, templates, delivery |
| Automation | Automation | Event/schedule rules |
| Workflows | Workflows | Approval & business flows |
| Scheduler | Scheduler | Cron / recurring platform jobs |
| AI Assistant | AI Assistant | Providers, prompts, chat |
| Analytics | Analytics | Delivery, automation, workflow, AI KPIs |

> **Sync** under Integrations is a separate sync engine — not the Scheduler.

## Day-1 setup checklist

1. Confirm queue worker is running (`php artisan queue:work` or Horizon).
2. Confirm scheduler is running (`php artisan schedule:work` or OS cron).
3. Seed roles/permissions (`RolesAndPermissionsSeeder`).
4. Seed AI null provider for safe local use (`AiSeeder`).
5. Review notification channels (Email/In-App enabled).
6. Publish required notification templates.
7. Create first automation rule in test mode / with safe actions.
8. Publish one simple approval workflow and test approve/reject.
9. Create a Health Check scheduled job and verify history.
10. Open Analytics Dashboard and confirm KPIs load.

## Role guidance

| Role | Typical access |
|------|----------------|
| Super Admin | Full Phase 8 permissions |
| Company Admin | Notifications, automation, workflows, scheduler, AI, analytics (+ export) |
| Developer | Scheduler + AI manage/chat (no full automation delete by default matrix — verify seeder) |
| Support Manager / Agent | Notifications view/create; automation view/update; AI chat; workflow approve |

Re-run `RolesAndPermissionsSeeder` after upgrades so new permissions (`ai.*`, `analytics.export`, etc.) sync.

## Notification Center ops

- Users manage preferences under Notifications → Preferences.
- Admins manage templates (draft → approve → publish).
- Delivery failures appear in Delivery Logs and Analytics Delivery Reports.
- Click rate requires future click instrumentation — currently schema-only.

## Automation ops

- Prefer `send_notification` / `send_email` / `assign_agent` for routine rules.
- Treat **Assign Role** and **Generate API Key** as privileged actions.
- Use History to audit success/failed/skipped runs.
- Disable a rule immediately via Toggle if misbehaving.

## Workflow ops

- Designers publish definitions before instances can run.
- Approvers need `workflows.approve`.
- Monitor overdue items; timeouts escalate via scheduled command.
- Do not confuse with Content Approvals (Content module).

## Scheduler ops

- Job types: cron, recurring, one-time, delayed, queue.
- Handlers such as Daily Report / Weekly Backup currently complete as **operational stubs** — verify side effects before relying on them in production.
- Use Failed + Retry for recoverable errors.
- Custom commands are allow-listed in code.

## AI ops

- Start with Null provider in non-prod.
- Add OpenAI / Azure / Gemini / Claude / Custom with encrypted API keys.
- Mark one provider as default.
- Monitor Usage Analytics and AI Logs for cost/latency.
- Publish prompts before expecting feature-specific behavior.

## Analytics ops

- Filter by date range; export CSV or Excel.
- PDF export is architecture-ready (returns readiness message until renderer ships).
- Click rate will be low until click tracking is wired end-to-end.

## Incident playbook (quick)

| Symptom | Check |
|---------|-------|
| No automation runs | `automation:process` schedule + queue + rule enabled |
| Notifications not received | Channel enabled, preferences, delivery logs, mail config |
| Workflow stuck | Instance status, approvers, timeout command |
| Scheduler “success” but no effect | Handler may be stub — inspect handler implementation |
| AI errors | Provider health test, credentials, driver registry |
| Analytics empty | Confirm source logs exist in period |

## Related permissions

See `docs/Permissions.md` and Spatie permission matrix from `PermissionModule` (`notifications`, `automation`, `workflows`, `scheduler`, `ai`, `analytics`).
