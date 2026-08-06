# Automation Documentation

## Overview

The Automation Rules Engine executes event-based, time-delayed, scheduled, and conditional automations.

**Domain:** `backend/app/Domains/Automation/`  
**Frontend:** `frontend/src/modules/automation/`  
**API prefix:** `/api/v1/automation`  
**Command:** `php artisan automation:process` (every minute)

## Trigger types

| Type | Behavior |
|------|----------|
| `event` | Runs when a mapped domain event fires |
| `time` | Queues for `delay_minutes` after an event |
| `schedule` | Cron expression + timezone via `automation:process` |

## Supported events

- `support.ticket_created`
- `support.ticket_assigned`
- `support.ticket_closed`
- `customer.created`
- `application.created`
- `application.release_deployed`

## Conditions

- AND / OR logic
- Field comparisons against event context
- Evaluated by `AutomationConditionEvaluator`

## Actions

| Action | Notes |
|--------|-------|
| `send_email` | Via NotificationDispatchService |
| `send_notification` | In-app / notification center |
| `create_task` | Customer/support task creation |
| `assign_agent` | Assign ticket agent |
| `assign_role` | **High privilege** — assign Spatie role |
| `generate_api_key` | **High privilege** — creates Sanctum token |
| `notify_customers` | Capped batch notify |

## Visual rule builder

Frontend pages:

- Automation Dashboard
- Rules list
- Visual builder (create/edit)
- Execution history

## Database

| Table | Purpose |
|-------|---------|
| `automation_rules` | Rule definitions (soft delete) |
| `automation_conditions` | Condition rows |
| `automation_actions` | Action rows |
| `automation_logs` | Execution history (`started_at` / `finished_at`) |

## Permissions

- `automation.view`
- `automation.create`
- `automation.update`
- `automation.delete`
- `automation.manage`

## Operational requirements

1. Queue worker must run for delayed/queued notification actions.
2. Scheduler must invoke `schedule:run` so `automation:process` fires every minute.
3. Treat `assign_role` and `generate_api_key` as privileged — restrict who can edit rules containing them.

## Related docs

- `docs/automation/Overview.md`
- Analytics: Automation Reports under `/analytics/automation`
