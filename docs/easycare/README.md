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
