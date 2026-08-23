<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'webhooks.create' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Create webhook
      </RouterLink>
      <RouterLink
        :to="{ name: 'integrations.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create integration
      </RouterLink>
    </Teleport>

    <IntegrationsHubSubnav />

    <div class="grid gap-6 xl:grid-cols-12">
      <aside class="xl:col-span-3">
        <nav
          class="sticky top-4 rounded-[12px] bg-white p-4 ring-1 ring-zinc-100"
          aria-label="Documentation sections"
        >
          <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
            Connect an app
          </p>
          <a
            v-for="item in toc"
            :key="item.id"
            :href="`#${item.id}`"
            class="block rounded-[10px] px-2 py-2 text-sm transition"
            :class="
              activeSection === item.id
                ? 'bg-brand-50 font-medium text-brand-700'
                : 'text-slate-600 hover:bg-zinc-50 hover:text-slate-900'
            "
            @click.prevent="goToSection(item.id)"
          >
            {{ item.label }}
          </a>
        </nav>
      </aside>

      <div class="min-w-0 space-y-6 xl:col-span-9">
        <section id="overview" class="scroll-mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-7">
          <h1 class="text-xl font-semibold tracking-tight text-slate-900">How AMS connects another app</h1>
          <p class="mt-2 text-sm leading-6 text-slate-600">
            Your website or mobile app does not need a custom AMS plugin. Post signed JSON to one incoming
            webhook for help, complaints, SMS, and privacy forms. AMS can also pull records from your REST
            API with Sync. When an agent sends a Public reply, AMS posts a signed payload back to your app.
          </p>

          <div class="mt-5 overflow-x-auto rounded-[12px] bg-slate-900 p-4 font-mono text-xs leading-6 text-slate-100">
            <pre>Your app  ── signed POST ──►  POST /api/v1/webhooks/incoming/{slug}
                                    │
                     Support ticket / Complaint / Privacy
                                    │
Public reply  ── signed POST ──►  Your outgoing URL (chat or SMS)

AMS Sync  ── GET your REST API ──►  import patients / records</pre>
          </div>

          <dl class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">API host</dt>
              <dd class="mt-1 break-all font-mono text-sm text-slate-900">{{ apiBase }}</dd>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Incoming URL</dt>
              <dd class="mt-1 break-all font-mono text-sm text-slate-900">{{ incomingUrl }}</dd>
            </div>
          </dl>
        </section>

        <section id="connect" class="scroll-mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-7">
          <h2 class="text-lg font-semibold text-slate-900">One-time setup in AMS</h2>
          <ol class="mt-4 space-y-3 text-sm text-slate-700">
            <li class="flex gap-3">
              <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">1</span>
              <span>
                <RouterLink class="font-medium text-brand-700 hover:underline" :to="{ name: 'companies.index' }">Companies</RouterLink>
                — create or select the company that owns the other app.
              </span>
            </li>
            <li class="flex gap-3">
              <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">2</span>
              <span>
                <RouterLink class="font-medium text-brand-700 hover:underline" :to="{ name: 'applications.index' }">Applications</RouterLink>
                — create an app card with a stable slug such as
                <code :class="codeClass">my-shop-web</code>.
              </span>
            </li>
            <li class="flex gap-3">
              <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">3</span>
              <span>
                <RouterLink class="font-medium text-brand-700 hover:underline" :to="{ name: 'integrations.create' }">Integrations</RouterLink>
                — REST base URL and token. Required for <strong>Sync</strong>. Optional for tickets.
              </span>
            </li>
            <li class="flex gap-3">
              <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">4</span>
              <span>
                <RouterLink class="font-medium text-brand-700 hover:underline" :to="{ name: 'webhooks.create' }">Webhooks → Create</RouterLink>
                — Direction <strong>Incoming</strong>, status Active, algorithm
                <code :class="codeClass">hmac_sha256</code>, header <code :class="codeClass">X-AMS-Signature</code>.
                Subscribe to <code :class="codeClass">support.message.received</code>,
                <code :class="codeClass">support.sms.received</code>,
                <code :class="codeClass">support.ticket.created</code>.
              </span>
            </li>
            <li class="flex gap-3">
              <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">5</span>
              <span>
                Copy the incoming URL and secret into the other app as
                <code :class="codeClass">AMS_WEBHOOK_URL</code> and <code :class="codeClass">AMS_WEBHOOK_SECRET</code>.
              </span>
            </li>
            <li class="flex gap-3">
              <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">6</span>
              <span>
                For agent replies, create a second webhook with Direction <strong>Outgoing</strong>
                pointing at your receive URL. Subscribe to
                <code :class="codeClass">support.reply.sent</code> and <code :class="codeClass">support.sms.sent</code>.
              </span>
            </li>
          </ol>
        </section>

        <section id="incoming" class="scroll-mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-7">
          <h2 class="text-lg font-semibold text-slate-900">Incoming webhook API</h2>
          <p class="mt-2 text-sm text-slate-600">
            Your app POSTs signed JSON. Do not send a Sanctum token. Sign the
            <strong>raw JSON body</strong> with HMAC-SHA256 using the webhook secret.
            The path accepts the webhook slug or UUID.
          </p>

          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <tbody class="divide-y divide-zinc-100 text-slate-800">
                <tr>
                  <th class="py-2 pr-4 font-medium text-slate-500">Method</th>
                  <td class="py-2 font-mono text-xs">POST</td>
                </tr>
                <tr>
                  <th class="py-2 pr-4 font-medium text-slate-500">URL</th>
                  <td class="py-2 font-mono text-xs">{{ incomingUrl }}</td>
                </tr>
                <tr>
                  <th class="py-2 pr-4 font-medium text-slate-500">Header</th>
                  <td class="py-2 font-mono text-xs">X-AMS-Signature: sha256={hmac_hex}</td>
                </tr>
                <tr>
                  <th class="py-2 pr-4 font-medium text-slate-500">Auth</th>
                  <td class="py-2">HMAC secret only</td>
                </tr>
              </tbody>
            </table>
          </div>

          <CodeSample label="JSON body" :code="incomingExample" />
          <CodeSample label="curl" :code="curlExample" />
        </section>

        <section id="forms" class="scroll-mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-7">
          <h2 class="text-lg font-semibold text-slate-900">Support, complaint, and compliance</h2>
          <p class="mt-2 text-sm text-slate-600">
            Complaint is not a separate AMS module. Send
            <code :class="codeClass">event: support.message.received</code> and set
            <code :class="codeClass">data.form_type</code>. Required:
            <code :class="codeClass">data.body</code> (or <code :class="codeClass">message</code> / <code :class="codeClass">description</code>).
            Send a unique <code :class="codeClass">message_id</code> so retries do not create duplicate tickets.
            SMS uses <code :class="codeClass">event: support.sms.received</code>.
          </p>

          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <thead class="text-xs uppercase tracking-wide text-slate-500">
                <tr>
                  <th class="pb-2 pr-4 font-medium">form_type</th>
                  <th class="pb-2 font-medium">Lands in AMS</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-100 text-slate-800">
                <tr v-for="row in formTypes" :key="row.type">
                  <td class="py-2 pr-4 font-mono text-xs">{{ row.type }}</td>
                  <td class="py-2">{{ row.lands }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <RouterLink
              :to="{ name: 'support.tickets.index' }"
              class="rounded-[12px] bg-zinc-50 px-4 py-3 text-sm font-medium text-slate-800 ring-1 ring-zinc-100 hover:ring-brand-200"
            >
              Open Support tickets
            </RouterLink>
            <RouterLink
              :to="{ name: 'compliance.dashboard' }"
              class="rounded-[12px] bg-zinc-50 px-4 py-3 text-sm font-medium text-slate-800 ring-1 ring-zinc-100 hover:ring-brand-200"
            >
              Open Compliance
            </RouterLink>
            <RouterLink
              :to="{ name: 'webhooks.logs' }"
              class="rounded-[12px] bg-zinc-50 px-4 py-3 text-sm font-medium text-slate-800 ring-1 ring-zinc-100 hover:ring-brand-200"
            >
              Open webhook logs
            </RouterLink>
          </div>
        </section>

        <section id="replies" class="scroll-mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-7">
          <h2 class="text-lg font-semibold text-slate-900">Agent replies (AMS → your app)</h2>
          <p class="mt-2 text-sm text-slate-600">
            Only <strong>Public</strong> agent replies leave AMS. Private and internal notes stay here.
            Chat and complaint tickets send <code :class="codeClass">support.reply.sent</code> with
            <code :class="codeClass">reply_mode: live_chat</code>. SMS tickets also send
            <code :class="codeClass">support.sms.sent</code>. Verify HMAC on the raw body using the outgoing webhook secret.
          </p>
          <CodeSample label="Outgoing envelope" :code="replyExample" />
        </section>

        <section id="sync" class="scroll-mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-7">
          <h2 class="text-lg font-semibold text-slate-900">Sync (AMS pulls your API)</h2>
          <p class="mt-2 text-sm text-slate-600">
            Sync is not a webhook. AMS calls your REST API using the Integration base URL plus the
            config source path. Keep the host on the integration. Use a path such as
            <code :class="codeClass">/api/v1/patients</code> — not a full local
            <code :class="codeClass">.test</code> URL. Your API must be reachable from the AMS server that runs the job.
          </p>
          <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-slate-700">
            <li>Set the Integration base URL and Bearer token.</li>
            <li>
              Create a config under
              <RouterLink class="font-medium text-brand-700 hover:underline" :to="{ name: 'sync.configs' }">Sync → Configs</RouterLink>.
            </li>
            <li>
              Run from the config page, or
              <code :class="codeClass">POST {{ apiBase }}/api/v1/sync/configs/&#123;uuid&#125;/run</code>
              with a Sanctum Bearer token.
            </li>
          </ol>
          <p class="mt-4 rounded-[12px] bg-amber-50 px-4 py-3 text-sm text-amber-900">
            If AMS is running at <code :class="codeClass">amsapi.eh.studio</code>, it cannot resolve local Herd hosts such as
            <code :class="codeClass">easycare-api.test</code>. Point the source at a public API
            (<code :class="codeClass">https://easycare.eh.studio</code>) or run AMS locally so it can see
            <code :class="codeClass">.test</code> domains.
          </p>
        </section>

        <section id="errors" class="scroll-mt-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-7">
          <h2 class="text-lg font-semibold text-slate-900">Responses and errors</h2>
          <p class="mt-2 text-sm text-slate-600">
            Dashboard APIs use Sanctum. Incoming webhooks do not.
          </p>
          <CodeSample label="Success envelope" :code="envelopeExample" />
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <thead class="text-xs uppercase tracking-wide text-slate-500">
                <tr>
                  <th class="pb-2 pr-4 font-medium">Code</th>
                  <th class="pb-2 font-medium">Meaning</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-100 text-slate-800">
                <tr>
                  <td class="py-2 pr-4 font-mono text-xs">200</td>
                  <td class="py-2">Webhook accepted (ticket ingest may continue after ACK)</td>
                </tr>
                <tr>
                  <td class="py-2 pr-4 font-mono text-xs">401</td>
                  <td class="py-2">Missing or invalid HMAC signature</td>
                </tr>
                <tr>
                  <td class="py-2 pr-4 font-mono text-xs">404</td>
                  <td class="py-2">Unknown webhook slug or UUID</td>
                </tr>
                <tr>
                  <td class="py-2 pr-4 font-mono text-xs">422</td>
                  <td class="py-2">Webhook inactive, or not incoming</td>
                </tr>
                <tr>
                  <td class="py-2 pr-4 font-mono text-xs">429</td>
                  <td class="py-2">Incoming webhook rate limited</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import CodeSample from '@/modules/integrations/components/CodeSample.vue';
import IntegrationsHubSubnav from '@/modules/integrations/components/IntegrationsHubSubnav.vue';

const codeClass = 'rounded-[4px] bg-zinc-100 px-1 py-0.5 font-mono text-xs text-slate-800';

const toc = [
  { id: 'overview', label: 'How it works' },
  { id: 'connect', label: 'Setup in AMS' },
  { id: 'incoming', label: 'Incoming webhook' },
  { id: 'forms', label: 'Support & complaint' },
  { id: 'replies', label: 'Agent replies' },
  { id: 'sync', label: 'Sync' },
  { id: 'errors', label: 'Errors' },
];

const activeSection = ref('overview');

const apiBase = computed(() => {
  const direct = String(import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '');
  if (direct) {
    return direct;
  }
  const proxy = String(import.meta.env.VITE_PROXY_TARGET || '').replace(/\/$/, '');
  if (proxy) {
    return proxy;
  }
  return 'https://amsapi.eh.studio';
});

const incomingUrl = computed(() => `${apiBase.value}/api/v1/webhooks/incoming/{slug-or-uuid}`);

const incomingExample = `{
  "event": "support.message.received",
  "timestamp": "2026-08-23T12:00:00+00:00",
  "data": {
    "message_id": "msg-1001",
    "body": "I cannot log in to my account.",
    "form_type": "support",
    "application_slug": "my-shop-web",
    "customer_name": "Ada Lovelace",
    "customer_email": "ada@example.com",
    "involves_personal_data": false
  }
}`;

const curlExample = computed(() => {
  const body =
    '{"event":"support.message.received","timestamp":"2026-08-23T12:00:00Z","data":{"message_id":"msg-1","body":"Login failed","form_type":"support","application_slug":"my-shop-web"}}';
  return `SECRET='your-webhook-secret'
BODY='${body}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')

curl -sS -X POST "${apiBase.value}/api/v1/webhooks/incoming/my-shop-web" \\
  -H "Content-Type: application/json" \\
  -H "X-AMS-Signature: sha256=\${SIG}" \\
  -d "$BODY"`;
});

const replyExample = `{
  "event": "support.reply.sent",
  "webhook_uuid": "…",
  "is_test": false,
  "sent_at": "2026-08-23T12:05:00+00:00",
  "data": {
    "ticket_uuid": "…",
    "ticket_number": "TCK-1042",
    "message_uuid": "…",
    "visibility": "public",
    "author_type": "agent",
    "body_plain": "We reset your password.",
    "reply_mode": "live_chat",
    "channel": "web",
    "application_slug": "my-shop-web"
  }
}`;

const envelopeExample = `{
  "success": true,
  "message": "Webhook received successfully.",
  "data": {
    "received": true,
    "event_name": "support.message.received",
    "log_uuid": "…"
  }
}`;

const formTypes = [
  { type: 'support, help, chat, sms, complaint, account_disable', lands: 'Support ticket' },
  { type: 'privacy, gdpr, data_deletion', lands: 'Support ticket + Privacy request' },
  { type: 'consent', lands: 'Privacy request only' },
  { type: 'compliance_case', lands: 'Compliance case only' },
  { type: 'breach', lands: 'Data breach only' },
  { type: 'dpia', lands: 'DPIA only' },
];

function goToSection(id) {
  activeSection.value = id;
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function onScroll() {
  const positions = toc
    .map((item) => {
      const el = document.getElementById(item.id);
      return el ? { id: item.id, top: el.getBoundingClientRect().top } : null;
    })
    .filter(Boolean);
  const current = [...positions].reverse().find((item) => item.top <= 120);
  if (current) {
    activeSection.value = current.id;
  }
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll);
});
</script>
