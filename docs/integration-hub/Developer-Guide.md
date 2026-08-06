# Integration Hub — Developer Guide

## Who this is for

Backend and frontend engineers extending AMS with new external systems, domain events, sync profiles, or jobs that must reuse Production Hub engines.

## Golden rules

1. **Never** call `Http::` / Guzzle from domain/feature code for outbound integrations.
2. **Always** go through shared engines listed below.
3. Keep controllers thin: FormRequest → authorize → Service → Resource/ApiResponse.
4. Prefer company-scoped data and Spatie permissions.
5. Log security-sensitive mutations with Spatie Activitylog where models already do.

---

## Engine map

| Need | Use |
|------|-----|
| Outbound HTTP | `App\Shared\Services\Http\ApiClientService` |
| Build connection from integration record | `ConnectionManager` via ApiClient / `IntegrationConnectionService` |
| Publish domain webhook | `WebhookService::dispatchEvent()` |
| Low-level webhook HTTP | `WebhookEngine` |
| Run sync | `IntegrationSyncService` / `SyncService` |
| Transform payloads | `DataMappingService::transformWithProfile()` / `MappingEngine` |
| Dispatch tracked jobs | Jobs using `config('ams_queue.types.*')` + `TrackQueuedJob` |
| Health metrics | Monitoring capture / `HealthMonitor` (ops, not business logic) |

---

## Adding a new outbound API call

```php
use App\Shared\Services\Http\ApiClientService;

public function __construct(private readonly ApiClientService $http) {}

public function pullCustomers(array $connection): array
{
    $response = $this->http->sendFromConnection($connection, [
        'method' => 'GET',
        'path' => '/v1/customers',
        'query' => ['page' => 1],
    ]);

    if (! $response->successful) {
        // handle via ApiException / domain exception
    }

    return (array) $response->json;
}
```

Prefer storing credentials on the `integrations` row (`credentials` encrypted cast) and updating via configuration API — do not hardcode secrets.

---

## Emitting webhooks from a new module

1. Ensure event exists in `webhook_events` (seeder or admin).
2. Call `dispatchEvent` after successful domain commit.
3. Do not implement ad-hoc HMAC; the engine signs payloads.

Optional: listen to domain events and dispatch from a Listener for cleaner services.

---

## Creating a sync profile

1. Register an active integration with base URL + auth.
2. Create sync config (direction, schedule, mapping profile).
3. Run manually (`POST /sync/configs/{uuid}/run`) or rely on `sync:dispatch-scheduled`.
4. Inspect runs/logs via Sync UI or API.

Mapping profiles should be created first when field shapes differ.

---

## Queued work

```php
// Prefer named AMS queues from config/ams_queue.php
public $queue = 'webhooks'; // or resolve from config('ams_queue.types.webhook.queue')

public function middleware(): array
{
    return [new TrackQueuedJob(/* type metadata */)];
}
```

Worker command (production):

```bash
php artisan queue:work redis --queue=high,imports,exports,webhooks,syncs,notifications,default,low --tries=3
```

Also run the scheduler for sync + monitoring.

---

## Permissions

| Area | Permissions |
|------|-------------|
| Hub CRUD / ops | `integrations.view\|create\|update\|delete\|manage` |
| Queue | `queue.view\|manage\|retry` |
| Monitoring | `monitoring.view\|manage` |

Register new permissions in seeders — never hardcode role names in services.

---

## Frontend modules

| Module path | Responsibility |
|-------------|----------------|
| `frontend/src/modules/integrations` | Registry + API connection UI |
| `frontend/src/modules/webhooks` | Webhook admin |
| `frontend/src/modules/sync` | Sync dashboard/configs |
| `frontend/src/modules/mappings` | Visual mapping builder |
| `frontend/src/modules/queue` | Queue dashboard |
| `frontend/src/modules/monitoring` | Health dashboards |

Use existing Pinia stores/services; call `/api/v1/...` via Axios client with Sanctum.

---

## Local verification

```bash
cd backend
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan test tests/Feature/Integrations tests/Feature/Queue tests/Feature/Monitoring
```

---

## Anti-patterns

| Avoid | Prefer |
|-------|--------|
| Fat controllers with HTTP calls | Service + ApiClientService |
| Per-feature webhook clients | WebhookService / Engine |
| Unencrypted secrets in JSON columns | Model `encrypted` / `encrypted:array` casts |
| Synchronous long sync in HTTP request | `RunIntegrationSyncJob` |
| Skipping permission middleware | Spatie permission + Policy |
| Leaving `signature_algorithm: none` in prod | `hmac_sha256` |

---

## Phase boundary

Phase 2 (Integration Hub) is complete pending Phase 2.8 approval.  
Do not start Phase 3 work until product approval is recorded.
