# Connect Any App → AMS Support (SMS / Messages)

Any website or mobile app can send support messages (including SMS) into AMS **Support** tickets using a signed **incoming webhook**. EasyCare is only one example — the same path works for every connected app.

## What AMS already provides

1. **Company** — tenant that owns the app  
2. **Integration** — API connection record (optional but recommended)  
3. **Application** — the website/app card in AMS  
4. **Incoming webhook** — public URL + shared HMAC secret  

When the app POSTs `support.sms.received` (or related events), AMS auto-creates a Support ticket (`source=sms` / `api` / …).

```
Your app / website
   │  (receives SMS via Twilio / local gateway / form / chat)
   │
   ▼
POST /api/v1/webhooks/incoming/{slug-or-uuid}
   Header: X-AMS-Signature: sha256={hmac}
   Body: { event, timestamp, data }
   │
   ▼
AMS verifies signature → webhook log → Support ticket
   │
   └─ if involves_personal_data=true → Compliance privacy route
```

---

## AMS setup (one time per app)

In AMS UI:

1. **Companies** → create/select the company  
2. **Integrations** → create integration (REST / Webhook type)  
3. **Applications** → create application linked to that company (and integration if available)  
4. **Webhooks** → Create  
   - Direction: **Incoming**  
   - Company: same company  
   - Integration: optional link  
   - Slug: unique, e.g. `my-shop-app`  
   - Status: **Active**  
   - Secret: long random shared secret  
   - Signature algorithm: `hmac_sha256`  
   - Signature header: `X-AMS-Signature` (or your preferred name — must match what the app sends)  
   - Subscribed events (recommended):  
     `support.sms.received`, `support.message.received`, `support.ticket.created`

Copy the **Incoming URL** from the webhook view page:

```
http://127.0.0.1:8080/api/v1/webhooks/incoming/{slug-or-uuid}
```

---

## What the external app must implement

### Required on the app side

| Item | Purpose |
|------|---------|
| Store `AMS_WEBHOOK_URL` | Incoming URL from AMS |
| Store `AMS_WEBHOOK_SECRET` | Same secret as AMS webhook |
| SMS / message receive hook | When user texts support (or submits help), build JSON and POST to AMS |
| HMAC-SHA256 signer | Sign **raw JSON body** with the secret |
| Idempotent `message_id` | Unique id per SMS/message so retries do not duplicate tickets |

AMS does **not** need direct Twilio credentials if the app already owns the SMS gateway. The app forwards the message.

### Optional on the app side

| Item | Purpose |
|------|---------|
| Outgoing AMS → app webhook | If you later want AMS ticket updates pushed back |
| REST integration + bearer token | For broader API sync (not required for Support SMS ingest) |
| Map `application_slug` | Links the ticket to the correct AMS Application |

### Minimal PHP example (app side)

```php
$payload = [
    'event' => 'support.sms.received',
    'timestamp' => now()->toIso8601String(),
    'data' => [
        'message_id' => $sms->uuid,          // required for idempotency
        'from' => $sms->from,                // +8801...
        'to' => $sms->to,
        'body' => $sms->body,                // required
        'customer_name' => $user->name ?? null,
        'customer_email' => $user->email ?? null,
        'application_slug' => 'my-shop-app', // optional
        'priority' => 'medium',              // optional
        'category' => 'customer_support',    // optional
        'involves_personal_data' => false,   // optional → Compliance route if true
    ],
];

$body = json_encode($payload, JSON_THROW_ON_ERROR);
$signature = 'sha256='.hash_hmac('sha256', $body, env('AMS_WEBHOOK_SECRET'));

Http::withHeaders([
    'Content-Type' => 'application/json',
    'X-AMS-Signature' => $signature,
])->withBody($body, 'application/json')
  ->post(env('AMS_WEBHOOK_URL'));
```

Raw signature (without `sha256=` prefix) is also accepted by AMS.

### Node.js sketch

```js
import crypto from 'crypto';

const payload = {
  event: 'support.sms.received',
  timestamp: new Date().toISOString(),
  data: {
    message_id: sms.id,
    from: sms.from,
    to: sms.to,
    body: sms.text,
  },
};

const body = JSON.stringify(payload);
const signature = 'sha256=' + crypto
  .createHmac('sha256', process.env.AMS_WEBHOOK_SECRET)
  .update(body)
  .digest('hex');

await fetch(process.env.AMS_WEBHOOK_URL, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-AMS-Signature': signature,
  },
  body,
});
```

---

## Standard events

| Event | Ticket `source` | Use when |
|-------|-----------------|----------|
| `support.sms.received` | `sms` | Inbound SMS to the app’s support number |
| `support.message.received` | `api` (or `channel`) | Web form, chat, in-app help |
| `support.ticket.created` | `api` (or `channel`) | App already modeled a ticket |

### `data` fields

| Field | Required | Notes |
|-------|----------|-------|
| `body` | **Yes** | Message text (`message` / `description` also accepted) |
| `message_id` | Strongly recommended | Idempotency (`sms_id` / `external_id` / `uuid` / `id` fallbacks) |
| `from` / `to` | Recommended for SMS | Phone numbers |
| `subject` | Optional | Defaults to “SMS support from …” |
| `customer_name` / `customer_email` / `customer_phone` | Optional | Shown in ticket description |
| `application_slug` / `application_uuid` | Optional | Links AMS Application |
| `category` | Optional | AMS Support categories |
| `priority` | Optional | `low` … `emergency` |
| `channel` | Optional | Overrides source for non-SMS events (`email`, `chat`, `web`, …) |
| `involves_personal_data` | Optional | `true` → auto Compliance privacy request |

---

## Where it appears in AMS

- **Support → Tickets** — new ticket, `source = SMS` (or API/Web/…)  
- **Webhooks → Logs** — receive + ingest result  
- **Compliance → Privacy Requests** — only if `involves_personal_data=true`

---

## EasyCare vs any app

| | EasyCare | Any other app |
|--|----------|---------------|
| Domain events (`patient.created`, …) | Yes (app-specific handler) | No |
| `support.sms.received` | Yes (generic handler) | Yes |
| Setup | Seeded company + webhook slug `easycare` | Create Integration + Application + Incoming webhook |

---

## Test locally

```bash
# After creating an incoming webhook with secret "test-secret" and slug "my-app"
php -r "
\$body = json_encode([
  'event' => 'support.sms.received',
  'timestamp' => date('c'),
  'data' => [
    'message_id' => 'sms-demo-1',
    'from' => '+8801700000000',
    'to' => '+8801800000000',
    'body' => 'Hello, I need help with my order.',
  ],
]);
\$sig = 'sha256=' . hash_hmac('sha256', \$body, 'test-secret');
echo \$body . PHP_EOL . \$sig . PHP_EOL;
"
```

Then POST that body to `/api/v1/webhooks/incoming/my-app` with header `X-AMS-Signature`.

Or run:

```bash
cd backend
php artisan test --filter=GenericSupportIncomingWebhookIngestTest
```
