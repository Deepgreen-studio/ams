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
| Outgoing AMS → app webhook | Required if agent replies in AMS must appear on the website / go out as SMS |
| Receive endpoint for AMS replies | Website URL that accepts signed POSTs from AMS |
| SMS send (if channel is SMS) | Website/gateway sends the agent reply text to the customer phone |
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

| Event | Ticket `source` | Use when | Reply mode back to app |
|-------|-----------------|----------|------------------------|
| `support.sms.received` | `sms` | Inbound SMS to the app’s support number | **SMS reply** (`support.sms.sent`) |
| `support.message.received` | `chat` / `web` / `api` / … | Live chat, Support, Complaint form | **Live chat** (`support.reply.sent`) |
| `support.ticket.created` | `api` (or `channel`) | App already modeled a ticket | Live chat or SMS from `channel` |

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
| `ticket_uuid` / `ticket_number` | Optional | **Follow-up** — append to existing AMS ticket Conversation instead of creating a new ticket |

---

## Where it appears in AMS

- **Support → Tickets** — new ticket, `source = SMS` (or API/Web/…)  
- **Support → Ticket → Conversation** — inbound SMS/message body as the first customer message  
- **Support → Ticket → Reply** — agent answers with Public / Private / Internal  
- **Compliance → Privacy Request → Linked Support conversation** — same reply path when the ticket was escalated for personal data (Public SMS replies still leave via outgoing webhook)  
- **Webhooks → Logs** — receive + ingest result  
- **Compliance → Privacy Requests** — only if `involves_personal_data=true`

---

## How agent replies show on the connected website

### Reply modes (important)

AMS uses **one Conversation UI** for everything. How the Public reply leaves AMS depends on the ticket channel:

| Inbound type | Ticket `source` / `channel` | Public reply behaves like | Connected website must |
|--------------|-----------------------------|---------------------------|------------------------|
| **Support** (help / live chat) | `chat`, `web`, `portal`, `api` | **Live chat** | Append message to the same chat thread (no SMS) |
| **Complaint** (same chat UX) | `chat`, `web`, `portal`, `api` | **Live chat** | Append message to the same chat / complaint thread |
| **SMS** | `sms` | **SMS reply** | Send plain-text SMS to customer phone via your gateway |

Same agent action in AMS: **Public reply → Send**.  
Different delivery on the website:

```
Support / Complaint  →  live chat bubble on website
SMS                  →  SMS text back to customer phone
```

Private reply and Internal note never leave AMS.

### What you see in AMS (already live)

On **Support → Tickets → open ticket**:

| UI block | Role |
|----------|------|
| **Conversation** | Full message history (customer + agent) — live-chat style thread |
| **Reply** | Composer: **Public reply** / Private reply / Internal note |

| Reply type | Visible in AMS Conversation | Leaves AMS to connected app |
|------------|-----------------------------|-----------------------------|
| **Public reply** | Yes | Yes — as **live chat** or **SMS**, based on ticket source |
| **Private reply** | Yes (staff) | No |
| **Internal note** | Yes (staff only) | No |

### End-to-end: Support / Complaint = live chat

```
Customer opens live chat / complaint form on website
        │
        ▼
POST support.message.received  (channel=chat or web)
        │
        ▼
AMS Support ticket → Conversation (chat thread)
        │
        ▼
Agent Public reply → Send
        │
        ▼
AMS Outgoing: support.reply.sent  (channel=chat|web|…)
        │
        ▼
Website: show agent bubble in the same live-chat / complaint thread
         (do NOT send SMS)
```

### End-to-end: SMS = SMS reply

```
Customer SMS → your website/gateway
        │
        ▼
POST support.sms.received → AMS ticket (source=sms)
        │
        ▼
Agent Public reply → Send
        │
        ▼
AMS Outgoing: support.reply.sent + support.sms.sent
        │
        ▼
Website / gateway: send body_plain as SMS to customer phone
         (also store in your SMS history if you have one)
```

### AMS setup for replies (Outgoing webhook)

In AMS UI (**after** Incoming is working):

1. **Webhooks → Create**
   - Direction: **Outgoing**
   - URL: your website endpoint, e.g. `https://your-site.test/api/ams/support-replies`
   - Company: same company as the Incoming webhook
   - Status: **Active**
   - Secret: shared secret (can match Incoming, or use a separate reply secret)
   - Signature algorithm: `hmac_sha256`
   - Subscribed events (recommended):  
     `support.reply.sent`, `support.sms.sent`, `support.ticket.updated`
2. Keep queue workers running (`webhooks` queue) so deliveries leave AMS
3. Confirm deliveries under **Webhooks → Logs**

### What the website must implement (receive AMS reply)

| Item | Purpose |
|------|---------|
| Public HTTPS (or local) URL | AMS POSTs agent replies here |
| HMAC verify | Same rules as Incoming (verify raw body) |
| Idempotency on `message_uuid` / `message_id` | Ignore duplicate deliveries |
| Thread mapping | Match `ticket_uuid` or original inbound `message_id` / `external_id` |
| Live-chat UI insert | If `channel` is `chat` / `web` / `portal` / `api` → append bubble to Support/Complaint thread |
| SMS send | If `channel` / event is SMS → send `body_plain` to customer phone |

### Expected payload (contract)

**Live chat / Support / Complaint** (`support.reply.sent`):

```json
{
  "event": "support.reply.sent",
  "timestamp": "2026-08-07T10:30:00+00:00",
  "data": {
    "ticket_uuid": "…",
    "ticket_number": "TKT-…",
    "message_uuid": "…",
    "message_id": "…",
    "visibility": "public",
    "author_type": "agent",
    "body": "<p>Thanks — we are looking into your complaint.</p>",
    "body_plain": "Thanks — we are looking into your complaint.",
    "channel": "chat",
    "source": "chat",
    "application_slug": "my-shop-app",
    "external_message_id": "chat-thread-42"
  }
}
```

**SMS reply** (also fires `support.sms.sent` with the same `data`):

```json
{
  "event": "support.sms.sent",
  "data": {
    "channel": "sms",
    "source": "sms",
    "body_plain": "Hello, how can I help you?",
    "customer_phone": "+8801700000000",
    "from": "+8801800000000",
    "to": "+8801700000000",
    "external_message_id": "sms-demo-1"
  }
}
```

Notes:

- Only **`visibility=public`** agent replies leave AMS.
- **Live chat rule:** if `channel` ≠ `sms` → show in website chat UI; do not SMS.
- **SMS rule:** if `event=support.sms.sent` or `channel=sms` → send SMS with **`body_plain`**.
- Prefer `body` (HTML) for chat UI; prefer `body_plain` for SMS.
- Payload also includes `reply_mode`: `live_chat` or `sms` — use this if you do not want to branch on `channel` yourself.

### Minimal PHP sketch (website receive)

```php
$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_AMS_SIGNATURE'] ?? '';
$expected = 'sha256='.hash_hmac('sha256', $raw, env('AMS_WEBHOOK_SECRET'));

if (! hash_equals($expected, $sig) && ! hash_equals(hash_hmac('sha256', $raw, env('AMS_WEBHOOK_SECRET')), $sig)) {
    abort(401);
}

$payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
$event = $payload['event'] ?? '';
$data = $payload['data'] ?? [];
$mode = $data['reply_mode'] ?? (
    (($data['channel'] ?? '') === 'sms' || $event === 'support.sms.sent') ? 'sms' : 'live_chat'
);

if ($mode === 'sms') {
    // SMS reply → send text to customer phone
    // SmsGateway::send($data['to'] ?? $data['customer_phone'], $data['body_plain']);
} elseif ($event === 'support.reply.sent') {
    // Support / Complaint → live chat bubble on same thread
    // ChatThread::appendAgentReply($data['ticket_uuid'], $data['body'], $data['external_message_id']);
}
```

### Current status (read before you connect)

| Direction | Status |
|-----------|--------|
| App → AMS (SMS / chat / message) | **Ready** — Incoming webhook → Support ticket + Conversation |
| App → AMS follow-up (`ticket_uuid`) | **Ready** — appends to same ticket Conversation |
| AMS Conversation (live-chat style UI) | **Ready** — Public / Private / Internal |
| AMS Support → App live chat (`support.reply.sent`) | **Ready** — Outgoing webhook on Public reply |
| AMS Support → App SMS (`support.sms.sent`) | **Ready** — also fired when ticket `source=sms` |
| AMS Compliance → App SMS / chat | **Ready** — Privacy Request linked conversation uses the same Public reply webhooks |

Connect **Incoming** first (customer message → AMS).  
Then ensure **Outgoing** webhook URL is set on the website so Public replies return as **live chat** or **SMS**.

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
