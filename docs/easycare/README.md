# EasyCare — Local API Connection (Support / Complaint / SMS / Compliance)

Connects AMS Integration Hub to the local **easycare-api** project (`k:\herd\easycare-api`).

## Seed (AMS)

```bash
cd k:\herd\ams\backend
php artisan db:seed --class=EasyCareCompanySeeder
```

Also included in `DatabaseSeeder`.

| Record | Details |
|--------|---------|
| **Company** | EasyCare |
| **Integration** | EasyCare API (`easycare-api`) |
| **Application** | EasyCare Web (`easycare-web`) |
| **Incoming webhook** | slug `easycare` — EasyCare → AMS |
| **Outgoing webhook** | slug `easycare-replies` — AMS Public replies → EasyCare |

## Local URLs (Herd)

| Service | Default |
|---------|---------|
| EasyCare API | `http://easycare-api.test` |
| AMS API | `http://ams.test` |
| Incoming webhook | `POST http://ams.test/api/v1/webhooks/incoming/easycare` |
| AMS → EasyCare replies | `POST http://easycare-api.test/api/v1/ams/support-replies` |
| EasyCare Support UI | `http://easycare-api.test/dashboard/sms` |

## Env (AMS `backend/.env`)

| Variable | Purpose |
|----------|---------|
| `EASYCARE_API_BASE_URL` | EasyCare base (default `http://easycare-api.test`) |
| `EASYCARE_SUPPORT_REPLY_URL` | Outgoing reply target |
| `EASYCARE_API_TOKEN` | Sanctum bearer for auth tests |
| `EASYCARE_AMS_WEBHOOK_SECRET` | Shared HMAC secret (default `easycare-ams-secret`) |

## Env (easycare-api `.env`)

```env
AMS_WEBHOOK_URL=http://ams.test/api/v1/webhooks/incoming/easycare
AMS_WEBHOOK_SECRET=easycare-ams-secret
AMS_APPLICATION_SLUG=easycare-web
AMS_SUPPORT_PHONE=+15550000999
AMS_FORWARD_SUPPORT_SMS=true
```

Sync the EasyCare outbound endpoint from env:

```bash
cd k:\herd\easycare-api
php artisan ams:sync-webhook
```

## Full Support / Complaint / SMS flow

```
EasyCare /dashboard/sms
   │  Support | Complaint | Privacy | SMS
   ▼
AmsSupportBridge (signed POST)
   │  X-EasyCare-Signature + X-AMS-Signature
   ▼
AMS Incoming /webhooks/incoming/easycare
   ▼
Support ticket (+ Compliance Privacy Request if personal data)
   ▼
Agent Public reply in AMS
   ▼
Outgoing easycare-replies → EasyCare /api/v1/ams/support-replies
   ▼
SMS reply (source=sms) or live chat bubble (web/complaint)
```

| EasyCare form | Event | AMS destination |
|---------------|-------|-----------------|
| SMS | `support.sms.received` | Support (`source=sms`) — Public reply returns as SMS |
| Help / Support | `support.message.received` | Support only |
| Complaint | `support.message.received` | Support only (live chat reply) |
| Privacy / GDPR | `support.message.received` + `involves_personal_data=true` | Support + Compliance |

## Signature

EasyCare sends both:

- `X-EasyCare-Signature` — raw HMAC-SHA256 (AMS EasyCare incoming webhook default)
- `X-AMS-Signature` — `sha256={hex}` (fallback)

AMS outgoing replies use `X-AMS-Signature`.

## Smoke test

```bash
# Terminals: Herd must serve ams.test + easycare-api.test

cd k:\herd\ams\backend
php artisan db:seed --class=EasyCareCompanySeeder

cd k:\herd\easycare-api
php artisan ams:sync-webhook
php artisan ams:verify-support-bridge --privacy
```

Or use the UI:

1. EasyCare → `/dashboard/sms` → send SMS / Complaint / Privacy  
2. AMS → **Support → Tickets**  
3. Privacy cases → **Compliance → Privacy Requests**  
4. Agent **Public reply** → appears back on EasyCare conversation (SMS or chat)

## Domain events (patients / blood sugar / etc.)

| EasyCare event | Support ticket | Compliance privacy request |
|----------------|----------------|----------------------------|
| `user.created` / `user.updated` | Yes | No |
| `appointment.created` | Yes | No |
| `easycare.test` | Yes | No |
| `patient.created` / `patient.updated` | Yes | Yes (auto-routed) |
| `blood_sugar.created` | Yes | Yes (auto-routed) |
| `medicine.updated` | Yes | Yes (auto-routed) |

Idempotency: duplicate deliveries with the same `data.uuid` reuse the existing ticket  
(`[easycare-ingest:{event}:{uuid}]` tag in the description).

System actor: `admin@ams.test` (fallback: webhook creator / company user).

## Related docs

- AMS: `docs/integration-hub/Connect-Website-Support-Compliance.md`
- AMS: `docs/integration-hub/Connect-Any-App-Support.md`
- EasyCare: `docs/Connect-Website-Support-Compliance.md`
