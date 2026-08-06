# Automation Domain — Phase 8.3 Rules Engine

## Overview

Enterprise Automation Engine for AMS. Supports event-based, time-delayed, scheduled, and conditional automation with a visual rule builder.

## Responsibilities

- Define automation rules with triggers, conditions, and actions
- Evaluate conditions (AND / OR) against event context
- Execute actions (email, in-app notification, tasks, assign agent/role, API keys, notify customers)
- Queue delayed rules and process scheduled cron rules
- Persist execution history in `automation_logs`

## Trigger Types

| Type | Description |
|------|-------------|
| `event` | Runs immediately when a domain event fires |
| `time` | Queues for `delay_minutes` after an event |
| `schedule` | Runs on cron (`schedule_cron` + timezone) |

## Supported Events

- `support.ticket_created`
- `support.ticket_assigned`
- `support.ticket_closed`
- `customer.created`
- `application.created`
- `application.release_deployed`

## Actions

| Action | Status |
|--------|--------|
| `send_email` | Implemented (via NotificationDispatchService) |
| `send_notification` | Implemented |
| `create_task` | Implemented |
| `assign_agent` | Implemented |
| `assign_role` | Implemented |
| `generate_api_key` | Implemented |
| `notify_customers` | Implemented |
| `send_push` | Future-ready (skipped) |

## Tables

- `automation_rules`
- `automation_conditions`
- `automation_actions`
- `automation_logs`

## API (prefix `/api/v1/automation`)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/dashboard` | Rule + log statistics and catalog |
| GET | `/catalog` | Triggers, events, operators, actions |
| GET | `/rules` | Paginated rules |
| POST | `/rules` | Create rule with conditions/actions |
| GET | `/rules/{uuid}` | Rule detail |
| PUT | `/rules/{uuid}` | Update rule |
| DELETE | `/rules/{uuid}` | Soft-delete rule |
| POST | `/rules/{uuid}/toggle` | Enable / disable |
| POST | `/rules/{uuid}/test` | Manual test run |
| GET | `/logs` | Execution history |

## Permissions

- `automation.view`
- `automation.create`
- `automation.update`
- `automation.delete`
- `automation.manage`

## Scheduler

`automation:process` runs every minute and processes due scheduled + delayed rules.

## Frontend

- Dashboard
- Rules list (search / filter / enable-disable)
- Visual rule builder (trigger → conditions → actions)
- History / logs

## Testing Notes

- Feature tests: `tests/Feature/Automation/AutomationEngineTest.php`
- Seed sample rules: `AutomationRulesSeeder`
