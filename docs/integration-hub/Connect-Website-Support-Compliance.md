# Connect Any Website → Support + Compliance (Auto-Route)

Use this guide when you want **another website or mobile app** to send helpdesk / complaint / privacy messages into AMS, and let AMS **automatically decide**:

| Destination | When |
|-------------|------|
| **Support only** | Normal help, login issues, complaints, account disable (operational) |
| **Support + Compliance** | Personal data / GDPR / health / “delete my data” style requests |

Incoming messages **always create a Support ticket first**. Compliance is an **auto-escalation** (linked Privacy Request), not a separate inbox API.

Related docs:

- [Connect Any App → Support](./Connect-Any-App-Support.md) — SMS / chat ingest + agent reply webhooks  
- [Webhooks](./Webhooks.md) — signature, retries, logs  
- [EasyCarbs Support ↔ Compliance Workflow](../easycarbs/Support-Compliance-Workflow.md) — product triage flowchart  
- [EasyCare local connection](../easycare/README.md) — full EasyCare-api ↔ AMS webhook bridge  

Copy-paste payloads: [`examples/`](./examples/).

### EasyCare-api (already wired)

If the other website is **EasyCare** (`k:\herd\easycare-api`):

```bash
cd k:\herd\ams\backend && php artisan db:seed --class=EasyCareCompanySeeder
cd k:\herd\easycare-api && php artisan ams:sync-webhook && php artisan ams:verify-support-bridge --privacy
```

Dashboard: `http://easycare-api.test/dashboard/sms`  
Bridge services: `AmsSupportBridge`, `SupportComplianceIntent`, `AmsReplyIngestService`  

---

## End-to-end picture

```
Your website (form / chat / SMS gateway)
        │
        │  POST signed JSON
        ▼
AMS Incoming Webhook
        │
        ▼
Support ticket created  ──────────────────────────────┐
        │                                               │
        │  involves_personal_data = true                │
        │  OR keyword match (gdpr, delete my data, …)   │
        ▼                                               │
Compliance Privacy Request (linked)                     │
        │                                               │
        └──────────── both stay linked ─────────────────┘
```

**Complaint** is not a separate AMS module. Complaint forms use the same Support path (`support.message.received` + `channel=web|chat`).

---

## One-time AMS setup (per website)

1. **Companies** → create/select the company that owns the website.  
2. **Integrations** (optional) → REST/Webhook connection record.  
3. **Applications** → create an app card; set a stable **slug** (e.g. `my-shop-web`).  
4. **Webhooks → Create** (Incoming):
   - Direction: **Incoming**
   - Company: same company
   - Slug: unique (e.g. `my-shop-web`)
   - Status: **Active**
   - Secret: long random shared secret
   - Signature: `hmac_sha256`, header `X-AMS-Signature`
   - Subscribed events (recommended):  
     `support.message.received`, `support.sms.received`, `support.ticket.created`
5. Copy **Incoming URL**:

```text
https://YOUR-AMS-HOST/api/v1/webhooks/incoming/{slug-or-uuid}
```

6. On the website, store:

| Env var | Value |
|---------|--------|
| `AMS_WEBHOOK_URL` | Incoming URL above |
| `AMS_WEBHOOK_SECRET` | Same secret as the AMS webhook |
| `AMS_APPLICATION_SLUG` | Application slug (e.g. `my-shop-web`) |

7. (Optional, for agent replies back to the site) create an **Outgoing** webhook — see [Connect Any App → Support](./Connect-Any-App-Support.md#how-agent-replies-show-on-the-connected-website).

---

## How AMS auto-routes Support vs Compliance

AMS runs `SupportComplianceRoutingService` when a Support ticket is created.

### Decision order

1. **Explicit flag (recommended)** — website sends `data.involves_personal_data: true|false`.  
2. **Support-only operational phrases** — even if flagged, these stay Support-only (e.g. “disable my account”).  
3. **Keyword fallback** — if flag is missing/`false`, subject + description are scanned for privacy/health language.

### Website form → flag mapping (do this in your app)

| Website form / intent | Set `involves_personal_data` | Lands in AMS |
|-----------------------|------------------------------|--------------|
| Help / Support / Live chat | `false` | **Support** |
| Complaint | `false` | **Support** (same conversation UX) |
| Temporarily disable / suspend account | `false` | **Support** only |
| Delete / erase my data, GDPR, privacy request | `true` | **Support + Compliance** Privacy Request |
| Health / medical personal data request | `true` | **Support + Compliance** |

Always send the flag from the website when you know the form type. Keywords are a safety net, not a substitute for clear product intent.

### Keyword fallback (AMS)

If the ticket subject/description contains any of these (case-insensitive), AMS escalates to Compliance:

- `health information`, `remove my health`
- `personal data`, `gdpr`, `data subject`, `right to be forgotten`
- `erase my data`, `delete my data`, `remove my data`
- `data deletion`, `data correction`, `privacy request`
- `blood glucose`, `blood pressure`

These stay **Support only**:

- `temporarily disable my account`, `disable my account`, `suspend my account`, `deactivate my account`

### Privacy request type (auto)

| Text contains | Privacy request type |
|---------------|----------------------|
| `delete` / `erase` / `forgotten` | `data_deletion` |
| `restrict` / `object` | `restrict_processing` |
| otherwise (personal-data path) | `data_correction` |

Breach / near-miss is **not** auto-opened from this path. That is a separate Compliance workflow.

### Where to look in the AMS UI

| Path | What you see |
|------|----------------|
| **Support → Tickets** | Always — new ticket |
| **Support → Ticket → Conversation** | Customer message + agent replies |
| **Compliance → Privacy Requests** | Only when escalated |
| **Privacy Request → Linked Support conversation** | Same thread when linked |
| **Webhooks → Logs** | Ingest result + `actions` |

Successful Compliance escalation often includes action `compliance_privacy_request_created` in the webhook ingest result.

---

## Event to use

| Use case | Event | Typical `channel` / source |
|----------|-------|----------------------------|
| Website support form / live chat | `support.message.received` | `web` or `chat` |
| Complaint form | `support.message.received` | `web` |
| Privacy / GDPR form | `support.message.received` | `web` + `involves_personal_data: true` |
| SMS into AMS | `support.sms.received` | `sms` |

Required fields and reply contracts: [Connect Any App → Support](./Connect-Any-App-Support.md#standard-events).

---

## Examples (copy-paste)

Replace `my-shop-web` with your Application slug. Sign every POST with HMAC-SHA256 of the **raw JSON body**.

### Example A — Support only (help form)

See also: [`examples/support-help.json`](./examples/support-help.json)

```json
{
  "event": "support.message.received",
  "timestamp": "2026-08-09T07:00:00+00:00",
  "data": {
    "message_id": "web-help-1001",
    "subject": "Cannot login",
    "body": "I forgot my password and cannot reset it.",
    "customer_name": "Rahim Ahmed",
    "customer_email": "rahim@example.com",
    "channel": "web",
    "priority": "medium",
    "category": "customer_support",
    "application_slug": "my-shop-web",
    "involves_personal_data": false
  }
}
```

**Expected:** Support ticket only. No Privacy Request.

---

### Example B — Complaint form (still Support)

See also: [`examples/support-complaint.json`](./examples/support-complaint.json)

```json
{
  "event": "support.message.received",
  "timestamp": "2026-08-09T07:05:00+00:00",
  "data": {
    "message_id": "web-complaint-55",
    "subject": "Complaint about delayed delivery",
    "body": "My order is 5 days late. Please resolve this complaint.",
    "customer_name": "Karim Hasan",
    "customer_email": "karim@example.com",
    "channel": "web",
    "priority": "high",
    "category": "customer_support",
    "application_slug": "my-shop-web",
    "involves_personal_data": false
  }
}
```

**Expected:** Support ticket (complaint thread). Agent Public reply returns via `support.reply.sent` as live chat — not SMS.

---

### Example C — Privacy / personal data → Support + Compliance

See also: [`examples/compliance-privacy.json`](./examples/compliance-privacy.json)

```json
{
  "event": "support.message.received",
  "timestamp": "2026-08-09T07:10:00+00:00",
  "data": {
    "message_id": "web-privacy-77",
    "subject": "Delete my health information",
    "body": "Please erase my data and remove my health information under GDPR.",
    "customer_name": "Nusrat Jahan",
    "customer_email": "nusrat@example.com",
    "customer_phone": "+8801700000000",
    "channel": "web",
    "priority": "high",
    "application_slug": "my-shop-web",
    "involves_personal_data": true
  }
}
```

**Expected:**

1. Support ticket created  
2. Auto Privacy Request in **Compliance → Privacy Requests**  
3. Ticket linked via `privacy_request_id` / `support_ticket_id`  
4. Ticket status moves toward pending / escalated workflow  

---

### Example D — Operational account disable (Support only)

See also: [`examples/support-disable-account.json`](./examples/support-disable-account.json)

```json
{
  "event": "support.message.received",
  "timestamp": "2026-08-09T07:15:00+00:00",
  "data": {
    "message_id": "web-disable-12",
    "subject": "Temporarily disable my account",
    "body": "Please temporarily disable my account while I travel.",
    "customer_email": "travel@example.com",
    "channel": "web",
    "application_slug": "my-shop-web",
    "involves_personal_data": false
  }
}
```

**Expected:** Support only (even if body mentions “account”). Not a Compliance privacy request.

---

### Example E — SMS support

See also: [`examples/support-sms.json`](./examples/support-sms.json)

```json
{
  "event": "support.sms.received",
  "timestamp": "2026-08-09T07:20:00+00:00",
  "data": {
    "message_id": "sms-9001",
    "from": "+8801700000000",
    "to": "+8801800000000",
    "body": "Hello, I need help with my order.",
    "application_slug": "my-shop-web",
    "involves_personal_data": false
  }
}
```

**Expected:** Support ticket `source=sms`. Agent Public reply can fire `support.sms.sent` for your gateway to send SMS.

---

## Website implementation examples

### Decide Support vs Compliance in your form handler (PHP)

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function amsIngestSupportMessage(array $input): void
{
    // Map your website form type → AMS routing flag
    $formType = $input['form_type'] ?? 'support'; // support | complaint | privacy

    $involvesPersonalData = in_array($formType, ['privacy', 'gdpr', 'data_deletion'], true);

    $payload = [
        'event' => 'support.message.received',
        'timestamp' => now()->toIso8601String(),
        'data' => [
            'message_id' => (string) ($input['message_id'] ?? Str::uuid()),
            'subject' => $input['subject'] ?? 'Website message',
            'body' => $input['body'] ?? $input['message'],
            'customer_name' => $input['name'] ?? null,
            'customer_email' => $input['email'] ?? null,
            'customer_phone' => $input['phone'] ?? null,
            'channel' => 'web',
            'application_slug' => env('AMS_APPLICATION_SLUG', 'my-shop-web'),
            'priority' => $input['priority'] ?? 'medium',
            'involves_personal_data' => $involvesPersonalData,
        ],
    ];

    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $secret = env('AMS_WEBHOOK_SECRET');
    $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

    Http::withHeaders([
        'Content-Type' => 'application/json',
        'X-AMS-Signature' => $signature,
    ])->withBody($body, 'application/json')
      ->post(env('AMS_WEBHOOK_URL'));
}
```

### Node.js (Express) sketch

```js
import crypto from 'crypto';

async function amsIngestSupportMessage({ formType, subject, body, email, name }) {
  const involvesPersonalData = ['privacy', 'gdpr', 'data_deletion'].includes(formType);

  const payload = {
    event: 'support.message.received',
    timestamp: new Date().toISOString(),
    data: {
      message_id: crypto.randomUUID(),
      subject,
      body,
      customer_name: name,
      customer_email: email,
      channel: 'web',
      application_slug: process.env.AMS_APPLICATION_SLUG || 'my-shop-web',
      involves_personal_data: involvesPersonalData,
    },
  };

  const raw = JSON.stringify(payload);
  const signature =
    'sha256=' +
    crypto.createHmac('sha256', process.env.AMS_WEBHOOK_SECRET).update(raw).digest('hex');

  await fetch(process.env.AMS_WEBHOOK_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-AMS-Signature': signature,
    },
    body: raw,
  });
}
```

### curl smoke test (bash / Git Bash)

```bash
SECRET="test-secret"
SLUG="my-shop-web"
URL="http://127.0.0.1:8080/api/v1/webhooks/incoming/${SLUG}"

BODY='{"event":"support.message.received","timestamp":"2026-08-09T07:00:00+00:00","data":{"message_id":"curl-1","subject":"Cannot login","body":"Need help logging in.","channel":"web","application_slug":"my-shop-web","involves_personal_data":false}}'
SIG="sha256=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')"

curl -sS -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-AMS-Signature: $SIG" \
  -d "$BODY"
```

Privacy variant: set `"involves_personal_data":true` and a privacy subject/body, then check **Compliance → Privacy Requests**.

### PHP one-liner to print body + signature

```bash
cd backend
php -r "
\$body = json_encode([
  'event' => 'support.message.received',
  'timestamp' => date('c'),
  'data' => [
    'message_id' => 'demo-privacy-1',
    'subject' => 'Delete my data',
    'body' => 'Please erase my data under GDPR.',
    'channel' => 'web',
    'application_slug' => 'my-shop-web',
    'involves_personal_data' => true,
  ],
], JSON_THROW_ON_ERROR);
\$sig = 'sha256=' . hash_hmac('sha256', \$body, 'test-secret');
echo \$body . PHP_EOL . \$sig . PHP_EOL;
"
```

---

## Checklist — connect a new website

- [ ] Company + Application (slug) created in AMS  
- [ ] Incoming webhook Active with shared secret  
- [ ] Website stores `AMS_WEBHOOK_URL` + `AMS_WEBHOOK_SECRET`  
- [ ] Website signs **raw JSON body** with HMAC-SHA256  
- [ ] Every message has unique `message_id` (idempotency)  
- [ ] Support / Complaint forms send `involves_personal_data: false`  
- [ ] Privacy / GDPR forms send `involves_personal_data: true`  
- [ ] Test Example A → appears under **Support → Tickets**  
- [ ] Test Example C → also appears under **Compliance → Privacy Requests**  
- [ ] (Optional) Outgoing webhook so agent Public replies return to the site  

---

## Backend reference (AMS)

| Piece | Location |
|-------|----------|
| Incoming generic ingest | `GenericSupportIncomingWebhookHandler` |
| Ticket create flag | `involves_personal_data` on Support ticket |
| Listener | `RoutePersonalDataTicketToCompliance` on `SupportTicketCreated` |
| Router | `SupportComplianceRoutingService` |
| Feature tests | `SupportComplianceRoutingTest`, `GenericSupportIncomingWebhookIngestTest` |

```bash
cd backend
php artisan test --filter=SupportComplianceRoutingTest
php artisan test --filter=GenericSupportIncomingWebhookIngestTest
```
