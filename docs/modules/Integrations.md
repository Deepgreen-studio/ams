# Integrations Module

## Overview

Enterprise Integration Hub for AMS.

- **Phase 2.1:** Integration registry foundation (CRUD)
- **Phase 2.2:** Reusable API Connection Engine + connection manager UI
- **Phase 2.3:** Webhook Engine
- **Phase 2.4:** API Synchronization Engine
- **Phase 2.5:** Data Mapping Engine (`docs/modules/DataMappings.md`)
- **Phase 2.6:** Queue Processing (`docs/modules/Queue.md`)
- **Phase 2.7:** Monitoring & Health Check (`docs/modules/Monitoring.md`)
- **Phase 2.8:** Hub review, consolidated docs & readiness reports (`docs/integration-hub/`)

All outbound HTTP for integrations MUST use `App\Shared\Services\Http\ApiClientService`. Business modules must never call Laravel `Http::` / Guzzle directly.

## Responsibilities

- Integration CRUD with soft delete and restore
- Encrypted credential storage on integrations
- API configuration (headers, query, timeout, retries, rate limit)
- Connection test and authentication test
- Request tester (GET/POST/PUT/PATCH/DELETE, JSON body, upload, download)
- Connection history with masked secrets
- Company-scoped registry and Spatie activity logging

## Folder Structure

```
backend/app/Shared/Services/Http/
  ApiClientService.php
  ConnectionManager.php
  AuthenticationManager.php
  RequestBuilder.php
  ResponseParser.php
  RetryManager.php
  TimeoutManager.php
  RateLimitManager.php
  DTOs/

backend/app/Domains/Integrations/
  Controllers/IntegrationConnectionController.php
  Services/IntegrationConnectionService.php
  Models/IntegrationConnectionLog.php
  ...

frontend/src/modules/integrations/
  pages/ApiConfigurationPage.vue
  pages/ConnectionTestPage.vue
  pages/RequestTesterPage.vue
  pages/ConnectionHistoryPage.vue
  components/ResponseViewer.vue
  ...
```

## Database

### `integrations` (extended)

Additional columns: `default_headers`, `default_query`, `rate_limit_per_minute`, `health_check_path`, `credentials` (encrypted JSON).

### `integration_connection_logs`

Outbound request history: method, URL, masked headers, truncated bodies, status, duration, attempts, success, actor.

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/api/v1/integrations` | List / create |
| GET/PUT/DELETE | `/api/v1/integrations/{uuid}` | Show / update / delete |
| POST | `/api/v1/integrations/{uuid}/restore` | Restore |
| PUT | `/api/v1/integrations/{uuid}/configuration` | API configuration + credentials |
| POST | `/api/v1/integrations/{uuid}/test-connection` | Connection test (no auth) |
| POST | `/api/v1/integrations/{uuid}/test-authentication` | Auth test |
| POST | `/api/v1/integrations/{uuid}/execute` | Request tester |
| GET | `/api/v1/integrations/{uuid}/history` | Connection history |
| GET | `/api/v1/integrations/{uuid}/history/{log}` | History detail |

## Permissions

- `integrations.view`
- `integrations.create`
- `integrations.update`
- `integrations.delete`
- `integrations.manage` (connection test / execute)

## Engine Components

| Component | Role |
|-----------|------|
| ApiClientService | Single entry for outbound HTTP |
| ConnectionManager | Resolve URL + merge defaults/auth |
| AuthenticationManager | API Key, Bearer, Basic, JWT, OAuth2 |
| RequestBuilder | Headers, query, JSON, multipart |
| ResponseParser | JSON/binary/download parsing |
| RetryManager | Retry with backoff on transient failures |
| TimeoutManager | Clamp timeout 1–300s |
| RateLimitManager | Per-integration per-minute limit (cache) |

## Events

- IntegrationCreated / Updated / Deleted / Restored
- IntegrationConfigurationUpdated
- IntegrationConnectionExecuted

## Testing

```bash
php artisan migrate
php artisan test --filter=IntegrationConnectionEngineTest
php artisan test --filter=IntegrationManagementTest
```

## Remaining (future)

- OAuth2 token refresh workers
- Scheduled health checks
- Full credentials vault separation
- Domain event auto-dispatch adapters (wire Users/Companies create/update → WebhookService::dispatchEvent)
- Scheduled retry worker for `next_retry_at` backlog