# Integration Hub — API Documentation

Base path: `/api/v1`  
Auth: Laravel Sanctum (`Authorization: Bearer {token}`) unless noted  
Response envelope:

```json
{ "success": true, "message": "", "data": {} }
```

Validation errors return `422` with `errors`. Unexpected failures return `success: false`.

---

## Integrations

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/integrations` | `integrations.view` | List (search, filters, sort, paginate) |
| POST | `/integrations` | `integrations.create` | Create registry entry |
| GET | `/integrations/{uuid}` | `integrations.view` | Show |
| PUT | `/integrations/{uuid}` | `integrations.update` | Update |
| DELETE | `/integrations/{uuid}` | `integrations.delete` | Soft delete |
| POST | `/integrations/{uuid}/restore` | `integrations.manage` | Restore |
| PUT | `/integrations/{uuid}/configuration` | `integrations.manage` | API config + credentials |
| POST | `/integrations/{uuid}/test-connection` | `integrations.manage` | Health check (no auth) |
| POST | `/integrations/{uuid}/test-authentication` | `integrations.manage` | Auth probe |
| POST | `/integrations/{uuid}/execute` | `integrations.manage` | Request tester |
| GET | `/integrations/{uuid}/history` | `integrations.view` | Connection logs |
| GET | `/integrations/{uuid}/history/{log}` | `integrations.view` | Log detail |

### Configuration payload (outline)

```json
{
  "base_url": "https://api.example.com",
  "auth_type": "bearer",
  "credentials": { "token": "..." },
  "default_headers": { "X-Client": "ams" },
  "default_query": {},
  "timeout": 30,
  "retry_attempts": 3,
  "rate_limit_per_minute": 60,
  "health_check_path": "/health"
}
```

Supported auth types (engine): API key, Bearer, Basic, JWT, OAuth2 (connection config driven).

---

## Webhooks

| Method | Path | Auth | Permission | Description |
|--------|------|------|------------|-------------|
| POST | `/webhooks/incoming/{uuid}` | HMAC signature | — | Incoming receive (`throttle:webhook-incoming`) |
| GET | `/webhooks` | Sanctum | `integrations.view` | List |
| POST | `/webhooks` | Sanctum | `integrations.create` | Create |
| GET | `/webhooks/{uuid}` | Sanctum | `integrations.view` | Show |
| PUT | `/webhooks/{uuid}` | Sanctum | `integrations.update` | Update |
| DELETE | `/webhooks/{uuid}` | Sanctum | `integrations.delete` | Delete |
| POST | `/webhooks/{uuid}/test` | Sanctum | `integrations.manage` | Test outgoing |
| GET | `/webhooks/logs` | Sanctum | `integrations.view` | Logs |
| GET | `/webhooks/logs/{uuid}` | Sanctum | `integrations.view` | Log detail |
| POST | `/webhooks/logs/{uuid}/retry` | Sanctum | `integrations.manage` | Retry failed |
| GET | `/webhooks/events` | Sanctum | `integrations.view` | Event catalog |
| GET | `/webhooks/events/{event}` | Sanctum | `integrations.view` | Event detail |

Incoming signature header: typically `X-Signature` / provider style; verified via `SignatureValidator` (`hmac_sha256` default).

---

## Sync

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/sync/dashboard` | `integrations.view` | Dashboard metrics |
| GET | `/sync/configs` | `integrations.view` | List configs |
| POST | `/sync/configs` | `integrations.create` | Create |
| GET | `/sync/configs/{uuid}` | `integrations.view` | Show |
| PUT | `/sync/configs/{uuid}` | `integrations.update` | Update |
| DELETE | `/sync/configs/{uuid}` | `integrations.delete` | Delete |
| POST | `/sync/configs/{uuid}/run` | `integrations.manage` | Queue sync run |
| GET | `/sync/runs` | `integrations.view` | Run history |
| GET | `/sync/runs/{uuid}` | `integrations.view` | Run detail |
| GET | `/sync/logs` | `integrations.view` | Sync logs |

Scheduled configs are dispatched by `sync:dispatch-scheduled` (every minute).

---

## Data Mappings

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/mappings/catalogs` | `integrations.view` | Field catalogs |
| GET | `/mappings` | `integrations.view` | List profiles |
| POST | `/mappings` | `integrations.create` | Create |
| GET | `/mappings/{uuid}` | `integrations.view` | Show |
| PUT | `/mappings/{uuid}` | `integrations.update` | Update |
| DELETE | `/mappings/{uuid}` | `integrations.delete` | Delete |
| POST | `/mappings/{uuid}/preview` | `integrations.view` | Transform preview |
| POST | `/mappings/{uuid}/validate` | `integrations.view` | Validate payload |

---

## Queue

Prefix: `/queue`  
Permissions: `queue.view`, `queue.manage`, `queue.retry`

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/queue/dashboard` | view | Dashboard |
| GET | `/queue/statistics` | view | Stats |
| GET | `/queue/tracks` | view | Tracked jobs |
| GET | `/queue/running` | view | Running |
| GET | `/queue/pending` | view | Pending |
| GET | `/queue/failed` | view | Failed list |
| GET | `/queue/failed/{id}` | view | Failed detail |
| POST | `/queue/failed/{id}/retry` | retry | Retry one |
| POST | `/queue/failed/retry-all` | retry | Retry all |
| DELETE | `/queue/failed/{id}` | manage | Forget one |
| DELETE | `/queue/failed` | manage | Flush failed |
| POST | `/queue/restart` | manage | Worker restart signal |
| POST | `/queue/sample` | manage | Dispatch sample job (dev/ops) |

---

## Monitoring

Prefix: `/monitoring`  
Permissions: `monitoring.view`, `monitoring.manage`

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/monitoring/dashboard` | view | Health + performance scores |
| GET | `/monitoring/api` | view | API monitor |
| GET | `/monitoring/webhooks` | view | Webhook monitor |
| GET | `/monitoring/queue` | view | Queue health |
| GET | `/monitoring/response-history` | view | Response history |
| POST | `/monitoring/capture` | manage | Force snapshot |
| GET/POST | `/monitoring/alerts` | view / manage | Alert CRUD |
| GET/PUT/DELETE | `/monitoring/alerts/{uuid}` | view / manage | Alert item |
| GET | `/monitoring/alert-events` | view | Fired events |
| POST | `/monitoring/alert-events/{uuid}/acknowledge` | manage | Acknowledge |

Capture also runs via `monitoring:capture` every five minutes.

---

## Status Codes

| Code | Meaning |
|------|---------|
| 200 / 201 | Success |
| 401 | Unauthenticated / bad webhook signature |
| 403 | Missing permission / policy deny |
| 404 | Not found |
| 422 | Validation |
| 429 | Rate limited (API or integration outbound) |
| 500 | Unexpected error |
