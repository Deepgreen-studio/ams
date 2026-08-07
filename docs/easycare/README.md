# EasyCare — Local API Connection

Connects AMS Integration Hub to the local **easycare-api** project (`k:\herd\easycare-api`).

## Seed

```bash
cd backend
php artisan db:seed --class=EasyCareCompanySeeder
```

Also included in `DatabaseSeeder`.

| Record | Details |
|--------|---------|
| **Company** | EasyCare |
| **Integration** | EasyCare API (`easycare-api`) |
| **Application** | EasyCare Web (`easycare-web`) |
| **Incoming webhook** | slug `easycare` |

## Local URLs

| Service | Default |
|---------|---------|
| EasyCare API | `http://127.0.0.1:8010` |
| AMS API | `http://127.0.0.1:8080` |
| Incoming webhook | `POST http://127.0.0.1:8080/api/v1/webhooks/incoming/easycare` |

## Env overrides (AMS)

| Variable | Purpose |
|----------|---------|
| `EASYCARE_API_BASE_URL` | EasyCare base (default `http://127.0.0.1:8010`) |
| `EASYCARE_API_TOKEN` | Sanctum bearer for auth tests |
| `EASYCARE_AMS_WEBHOOK_SECRET` | Shared HMAC secret (default `easycare-ams-secret`) |

## Env (easycare-api)

```env
AMS_WEBHOOK_URL=http://127.0.0.1:8080/api/v1/webhooks/incoming/easycare
AMS_WEBHOOK_SECRET=easycare-ams-secret
```

After changing env, refresh the seeded endpoint:

```bash
cd k:\herd\easycare-api
php artisan tinker --execute="App\Domains\Webhooks\Models\WebhookEndpoint::where('name','AMS Integration')->update(['url'=>env('AMS_WEBHOOK_URL'),'secret'=>env('AMS_WEBHOOK_SECRET')]);"
```

## Signature

EasyCare sends `X-EasyCare-Signature` (raw HMAC-SHA256 of body). AMS webhook is configured with the same header and secret.

## Connection test

In AMS UI: Integrations → EasyCare API → Test connection  
(or `POST /api/v1/integrations/{uuid}/test-connection`)

## Auto-ingest (Support + Compliance)

When EasyCare posts a signed webhook to AMS, AMS:

1. Verifies `X-EasyCare-Signature`
2. Writes a `webhook_logs` row
3. Queues / runs `IncomingWebhookIngestService` (inline; retries via `ProcessIncomingWebhookJob` on failure)
4. `EasyCareIncomingWebhookHandler` creates a **Support** ticket (`source=api`)
5. Health / personal-data events also set `involves_personal_data=true`, which triggers
   `SupportComplianceRoutingService` → **Compliance** privacy request (linked to the ticket)

| EasyCare event | Support ticket | Compliance privacy request |
|----------------|----------------|----------------------------|
| `user.created` / `user.updated` | Yes | No |
| `appointment.created` | Yes | No |
| `easycare.test` | Yes | No |
| `patient.created` / `patient.updated` | Yes | Yes (auto-routed) |
| `blood_sugar.created` | Yes (priority escalates on extreme values) | Yes (auto-routed) |
| `medicine.updated` | Yes | Yes (auto-routed) |

Idempotency: duplicate deliveries with the same `data.uuid` reuse the existing ticket
(`[easycare-ingest:{event}:{uuid}]` tag in the description).

System actor: `admin@ams.test` (fallback: webhook creator / company user).
