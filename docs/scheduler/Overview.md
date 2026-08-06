# Scheduler Domain — Phase 8.5 Enterprise Scheduler

## Overview

Enterprise Scheduler for AMS. Manages cron, recurring, one-time, delayed, and queue background jobs with dashboard, history, running/failed views, retry, logs, and statistics.

## Responsibilities

- Define scheduled jobs with handlers and schedules
- Process due jobs via `scheduler:process`
- Persist run history and detailed logs
- Support manual run and failed-run retry
- Provide catalog of enterprise handlers

## Job Types

| Type | Description |
|------|-------------|
| `cron` | Cron expression based |
| `recurring` | Recurring schedule (cron-backed) |
| `one_time` | Single future `run_at` |
| `delayed` | Run once after `delay_minutes` |
| `queue` | Dispatch execution to Laravel queue |

## Handlers

- Daily Report
- Weekly Backup
- Monthly Invoice
- Health Check (`monitoring:capture`)
- Customer Reminder
- Subscription Renewal
- Delete Expired Data
- Custom Artisan Command (allow-listed)

## Tables

- `scheduled_jobs`
- `scheduled_job_runs`
- `scheduled_job_logs`

## API (prefix `/api/v1/scheduler`)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/dashboard` | Stats + recent runs/failures |
| GET | `/catalog` | Types, handlers, common cron |
| GET | `/statistics` | Aggregated metrics |
| GET | `/jobs` | Job definitions |
| POST | `/jobs` | Create job |
| GET/PUT/DELETE | `/jobs/{uuid}` | CRUD |
| POST | `/jobs/{uuid}/toggle` | Enable/disable |
| POST | `/jobs/{uuid}/run` | Run now |
| GET | `/history` | Run history |
| GET | `/running` | Running runs |
| GET | `/failed` | Failed runs |
| POST | `/runs/{uuid}/retry` | Retry failed run |
| GET | `/logs` | Execution logs |

## Permissions

- `scheduler.view`
- `scheduler.create`
- `scheduler.update`
- `scheduler.delete`
- `scheduler.manage`
- `scheduler.retry`

## Scheduler tick

`scheduler:process` runs every minute.

## Frontend

Dashboard · Jobs · History · Running · Failed · Logs · Statistics

## Notes

- Sync integrations remain under Sync UI (renamed from mislabeled “Scheduler”)
- Queue domain remains the worker/queue monitor; Scheduler owns job definitions
- Feature tests: `tests/Feature/Scheduler/SchedulerEngineTest.php`
- Seeder: `ScheduledJobSeeder`
