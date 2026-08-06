# Webhooks Module (Phase 2.3)

## Overview

Enterprise Webhook Engine for AMS.

Supports incoming and outgoing webhooks, queued delivery, retries, signature validation, history/logs, and an event catalog.

**Future modules MUST use this engine** via:

- `App\Shared\Services\Webhook\WebhookEngine` for low-level deliver/receive/signature
- `App\Domains\Integrations\Services\WebhookService::dispatchEvent()` to fan out domain events to subscribed outgoing webhooks

Never implement one-off webhook HTTP clients in business modules. Outgoing delivery always uses `ApiClientService`.

## Database

- `webhook_events` — event catalog
- `webhooks` — incoming/outgoing endpoint registry
- `webhook_logs` — delivery/receive history

## Shared Engine

```
Shared/Services/Webhook/
  WebhookEngine.php
  WebhookDispatcher.php
  WebhookReceiver.php
  SignatureValidator.php
  WebhookRetryManager.php
  DTOs/WebhookDeliveryResult.php
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET/POST | `/api/v1/webhooks` | Sanctum | List / create |
| GET/PUT/DELETE | `/api/v1/webhooks/{uuid}` | Sanctum | Show / update / delete |
| POST | `/api/v1/webhooks/{uuid}/test` | Sanctum + manage | Outgoing test delivery |
| GET | `/api/v1/webhooks/logs` | Sanctum | Webhook logs |
| GET | `/api/v1/webhooks/logs/{uuid}` | Sanctum | Log detail |
| POST | `/api/v1/webhooks/logs/{uuid}/retry` | Sanctum + manage | Retry failed |
| GET | `/api/v1/webhooks/events` | Sanctum | Event catalog |
| POST | `/api/v1/webhooks/incoming/{uuid}` | Signature | Incoming receive |

## Permissions

Uses `integrations.view|create|update|delete|manage`.

## Queue

`DeliverOutgoingWebhookJob` on queue `webhooks`.

## Frontend

- Webhook List / Create / Edit / Details
- Webhook Logs (+ retry)
- Webhook Event Viewer
- Webhook Testing Tool

## Testing

```bash
php artisan migrate
php artisan db:seed --class=WebhookEventSeeder
php artisan test --filter=WebhookEngineTest
```

## Usage from future modules

```php
app(WebhookService::class)->dispatchEvent(
    eventName: 'user.created',
    payload: ['user_uuid' => $user->uuid],
    companyId: $companyId,
    actor: $actor,
);
```
