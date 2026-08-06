# Integration Hub — Webhook Documentation

## Overview

The Webhook Engine supports:

- **Outgoing** — AMS pushes events to subscriber URLs (queued, signed, retried)
- **Incoming** — External systems POST to AMS public receive URLs (signature verified)
- **Catalog** — Named events in `webhook_events`
- **Observability** — `webhook_logs` + Monitoring webhook score

## Architecture

```
Domain event / Admin action
        │
        ▼
WebhookService::dispatchEvent()
        │
        ▼
WebhookEngine / WebhookDispatcher
        │
        ▼
DeliverOutgoingWebhookJob  →  queue: webhooks
        │
        ▼
ApiClientService (signed HTTP POST)
        │
        ▼
webhook_logs (+ monitoring aggregation)
```

Incoming path:

```
POST /api/v1/webhooks/incoming/{uuid}
        │  throttle:webhook-incoming
        ▼
WebhookReceiver + SignatureValidator
        │
        ▼
Persist log / dispatch internal handling
```

## Tables

| Table | Purpose |
|-------|---------|
| `webhooks` | Endpoint registry (direction, URL, secret encrypted, events, status) |
| `webhook_logs` | Delivery / receive attempts |
| `webhook_events` | Event catalog |

## Signatures

Implemented in `App\Shared\Services\Webhook\SignatureValidator`.

| Algorithm | Behavior |
|-----------|----------|
| `hmac_sha256` (default) | HMAC-SHA256; header form `sha256={hex}` |
| `hmac_sha1` | HMAC-SHA1 |
| `none` | Skips verification (**not recommended** for production partners) |

Verification uses `hash_equals` (timing-safe). Secrets are stored encrypted on the `webhooks` model.

### Outgoing signing

Payload body is signed with the webhook secret before delivery. Partners should verify using the same algorithm.

### Incoming verification

```http
POST /api/v1/webhooks/incoming/{uuid}
Content-Type: application/json
X-Signature: sha256={hmac_hex}

{ "event": "order.created", "data": { } }
```

Invalid signatures return **401**.

## Retries

- `WebhookRetryManager` + job-level tries/backoff from `config/ams_queue.php`
- Failed deliveries appear in logs; operators retry via  
  `POST /api/v1/webhooks/logs/{uuid}/retry`

## Queue

Worker must include `webhooks`:

```bash
php artisan queue:work --queue=high,imports,exports,webhooks,syncs,notifications,default,low
```

## Event Dispatch (from code)

```php
use App\Domains\Integrations\Services\WebhookService;

app(WebhookService::class)->dispatchEvent(
    eventName: 'user.created',
    payload: [
        'user_uuid' => $user->uuid,
        'email' => $user->email,
    ],
    companyId: $companyId,
    actor: $actor,
);
```

Never call Laravel `Http::` / Guzzle from domain modules for webhooks.

## Admin UI

| Page | Route name |
|------|------------|
| List | `webhooks.index` |
| Create / Edit / View | `webhooks.create` / `edit` / `show` |
| Tester | `webhooks.tester` |
| Logs | `webhooks.logs` |
| Events | `webhooks.events` |
| Monitoring | `monitoring.webhooks` |

## Testing

```bash
cd backend
php artisan test tests/Feature/Integrations/WebhookEngineTest.php
```

Coverage includes create/list, signed outgoing test, retry, incoming accept/reject, events and logs list.

## Operational Checklist

1. Seed webhook events (`WebhookEventSeeder`)
2. Create outgoing webhook with secret ≠ empty and algorithm ≠ `none` in production
3. Subscribe only required events
4. Confirm queue workers + Redis/database queue healthy
5. Monitor webhook score / alert rules in Monitoring
