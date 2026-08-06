<template>
  <div>
    <PageHeader
      title="Webhook Testing Tool"
      description="Send a signed test delivery through the Webhook Engine queue/delivery pipeline."
    />
    <WebhookSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="mb-4 text-sm text-slate-600">
          Testing:
          <span class="font-medium text-slate-900">{{ store.currentWebhook?.name || '…' }}</span>
        </div>
        <form class="space-y-4" @submit.prevent="runTest">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Event name</label>
            <input
              v-model="form.event_name"
              type="text"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">JSON payload</label>
            <textarea
              v-model="payloadText"
              rows="10"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 font-mono text-xs"
            />
          </div>
          <button
            type="submit"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.saving || store.currentWebhook?.direction !== 'outgoing'"
          >
            {{ store.saving ? 'Sending...' : 'Send test webhook' }}
          </button>
          <p v-if="store.currentWebhook?.direction === 'incoming'" class="text-xs text-amber-700">
            Incoming webhooks are tested by POSTing to {{ store.currentWebhook.incoming_url }} with
            a valid signature.
          </p>
        </form>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Result</h3>
        <div v-if="!store.lastTestLog" class="mt-4 text-sm text-slate-500">No test result yet.</div>
        <dl v-else class="mt-4 space-y-3 text-sm">
          <div>
            <dt class="text-xs uppercase text-slate-500">Status</dt>
            <dd class="font-medium text-slate-900">{{ store.lastTestLog.status }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-slate-500">HTTP</dt>
            <dd>
              {{ store.lastTestLog.response_status || '—' }} ·
              {{ store.lastTestLog.duration_ms }} ms
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-slate-500">Attempts</dt>
            <dd>{{ store.lastTestLog.attempts }} / {{ store.lastTestLog.max_attempts }}</dd>
          </div>
          <div v-if="store.lastTestLog.error_message">
            <dt class="text-xs uppercase text-slate-500">Error</dt>
            <dd class="text-rose-700">{{ store.lastTestLog.error_message }}</dd>
          </div>
          <div>
            <dt class="mb-1 text-xs uppercase text-slate-500">Response body</dt>
            <pre
              class="max-h-64 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100"
              >{{ store.lastTestLog.response_body || '—' }}</pre>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const route = useRoute();
const store = useWebhooksStore();
const payloadText = ref('{\n"message": "AMS webhook test"\n}');
const form = reactive({ event_name: 'webhook.test' });

onMounted(() => {
  store.lastTestLog = null;
  store.fetchWebhook(route.params.id);
});

async function runTest() {
  let payload = {};
  try {
    payload = payloadText.value.trim() ? JSON.parse(payloadText.value) : {};
  } catch {
    store.error = 'Payload must be valid JSON.';
    return;
  }
  await store.testWebhook(route.params.id, { event_name: form.event_name, payload });
}
</script>
