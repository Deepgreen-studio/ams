# Sync Engine Module (Phase 2.4)

## Overview

Enterprise API Synchronization Engine for AMS.

Supports manual, automatic, scheduled, and background syncs with full/incremental modes, conflict resolution, progress tracking, and run/logs history.

**Future modules MUST use this engine** via:

- `App\Shared\Services\Sync\SyncService` for orchestration (import/export + conflict resolution)
- `App\Domains\Integrations\Services\IntegrationSyncService` for persisted configs, runs, scheduling, and queue dispatch

Never implement one-off sync loops in business modules. Remote HTTP always goes through `ApiClientService`.

## Support Matrix

| Capability | Support |
|------------|---------|
| Manual Sync | `POST /sync/configs/{uuid}/run` |
| Automatic Sync | Config `trigger_type=automatic` |
| Scheduled Sync | Cron + `sync:dispatch-scheduled` |
| Background Sync | Queue job `RunIntegrationSyncJob` (`syncs`) |
| Incremental Sync | Mode `incremental` + cursor / checksum skip |
| Full Sync | Mode `full` |

## Tracking

Each `sync_runs` row tracks:

- `started_at`, `completed_at`, `failed_at`
- `total_records`, `imported`, `exported`, `updated`, `failed`, `skipped`
- `progress_percent`

## Database

- `sync_configs` — sync definitions tied to integrations
- `sync_runs` — execution history + counters
- `sync_logs` — record-level action logs

## Shared Engine

```
Shared/Services/Sync/
  SyncService.php
  ImportManager.php
  ExportManager.php
  Scheduler.php
  ConflictResolver.php
  DTOs/SyncRecordDto.php
  DTOs/SyncResultDto.php
```

## Domain

```
Domains/Integrations/
  Controllers/SyncController.php
  Services/IntegrationSyncService.php
  Jobs/RunIntegrationSyncJob.php
  Models/SyncConfig|SyncRun|SyncLog.php
  Repositories/Sync*Repository.php
  Policies/SyncPolicy.php
  Commands (console): sync:dispatch-scheduled
```

## API Endpoints

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/api/v1/sync/dashboard` | integrations.view | Totals + recent runs |
| GET/POST | `/api/v1/sync/configs` | view / create | List / create configs |
| GET/PUT/DELETE | `/api/v1/sync/configs/{uuid}` | view / update / delete | Config CRUD |
| POST | `/api/v1/sync/configs/{uuid}/run` | integrations.manage | Run sync |
| GET | `/api/v1/sync/runs` | integrations.view | Sync history |
| GET | `/api/v1/sync/runs/{uuid}` | integrations.view | Run detail |
| GET | `/api/v1/sync/logs` | integrations.view | Sync logs |

### Run payload

```json
{
  "mode": "full",
  "background": true
}
```

- In production, `background: true` queues `RunIntegrationSyncJob`.
- In testing (or `background: false`), the run processes synchronously.

### Local sample sync

Configs may include `options.sample_records` (array) for foundation/import testing without a live remote. Remote imports use the integration connection via `ApiClientService`.

## Schedule

```php
Schedule::command('sync:dispatch-scheduled')->everyMinute()->withoutOverlapping();
```

Queue worker:

```bash
php artisan queue:work --queue=syncs,webhooks,default
```

## Permissions

Uses `integrations.view|create|update|delete|manage`.

## Frontend

- Sync Dashboard (totals, recent runs, progress)
- Sync Configs (list/create/edit/details + Run Sync)
- Sync History (filters, progress bars, counters)
- Sync Logs (level/action filters, run UUID)

Routes under `/sync/*`, sidebar entry **Sync**.

## Testing

```bash
php artisan migrate
php artisan test --filter=SyncEngineTest
```

## Usage from future modules

```php
app(IntegrationSyncService::class)->run(
    $configUuid,
    $actor,
    trigger: 'automatic',
    mode: 'incremental',
    background: true,
);
```

Or compose the shared engine directly:

```php
$result = app(SyncService::class)->import($records, $options);
```
