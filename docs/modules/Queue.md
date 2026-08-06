# Queue Processing (Phase 2.6)

## Overview

Enterprise background processing for AMS on Laravel Queue (database driver by default).

Provides standardized Import / Export / Webhook / Sync / Notification jobs, priority queues, retries with backoff, delays, a job track monitor, failed-job management, and a Vue Queue Dashboard.

## Job Classes

| Job | Queue | Type |
|-----|-------|------|
| `ProcessImportJob` | `imports` | import |
| `ProcessExportJob` | `exports` | export |
| `DeliverOutgoingWebhookJob` | `webhooks` | webhook |
| `RunIntegrationSyncJob` | `syncs` | sync |
| `ProcessNotificationJob` | `notifications` | notification |

Priority queues: `high`, `default`, `low`.

Recommended worker:

```bash
php artisan queue:work --queue=high,imports,exports,webhooks,syncs,notifications,default,low
```

## Support

- Retry (failed jobs + tries/backoff on jobs)
- Delay (`delay_seconds` on dispatch)
- Priority queue (`high` / `normal` / `low`)
- Queue Monitor (`queue_job_tracks` + TrackQueuedJob middleware)
- Failed Jobs (`failed_jobs` + API)
- Restart Jobs (`queue:restart` signal)

## Shared Engine

```
Shared/Services/Queue/
  QueueManager.php
  QueueMonitor.php
  Middleware/TrackQueuedJob.php
config/ams_queue.php
```

## Domain

`Domains/Queue` — dashboard APIs, permissions, tracks repository.

## Database

- `jobs` / `failed_jobs` / `job_batches` (Laravel)
- `queue_job_tracks` — AMS monitor for queued/running/completed/failed with type/priority

## Permissions

- `queue.view`
- `queue.manage`
- `queue.retry`

Re-seed:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/queue/dashboard` | Totals, sizes, recent |
| GET | `/api/v1/queue/statistics` | Aggregates |
| GET | `/api/v1/queue/tracks` | Monitor history |
| GET | `/api/v1/queue/running` | Running tracks |
| GET | `/api/v1/queue/pending` | Jobs table pending |
| GET | `/api/v1/queue/failed` | Failed jobs |
| POST | `/api/v1/queue/failed/{uuid}/retry` | Retry one |
| POST | `/api/v1/queue/failed/retry-all` | Retry all |
| DELETE | `/api/v1/queue/failed/{uuid}` | Forget one |
| DELETE | `/api/v1/queue/failed` | Flush failed |
| POST | `/api/v1/queue/restart` | Restart workers |
| POST | `/api/v1/queue/sample` | Dispatch sample notification |

## Frontend

Sidebar **Queue**:

- Dashboard
- Running Jobs
- Failed Jobs (retry / flush)
- Statistics

## Testing

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan test --filter=QueueProcessingTest
```
