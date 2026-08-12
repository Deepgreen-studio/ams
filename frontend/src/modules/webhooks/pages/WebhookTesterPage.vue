<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div v-if="store.currentWebhook" class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{ name: 'webhooks.show', params: { id: store.currentWebhook.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          <EyeIcon class="h-4 w-4 text-slate-500" />
          View webhook
        </RouterLink>
        <RouterLink
          :to="{ name: 'webhooks.edit', params: { id: store.currentWebhook.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </RouterLink>
      </div>
    </Teleport>

    <WebhookSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div
      v-if="store.loading && !store.currentWebhook"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else class="grid gap-6 xl:grid-cols-2">
      <section class="overflow-hidden rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Send test</h2>
            <p class="mt-1 text-sm text-slate-500">
              Testing
              <span class="font-medium text-slate-800">{{
                store.currentWebhook?.name || '…'
              }}</span>
            </p>
          </div>
          <div class="flex flex-wrap gap-1.5">
            <DirectionBadge
              v-if="store.currentWebhook?.direction"
              :direction="store.currentWebhook.direction"
            />
            <StatusBadge
              v-if="store.currentWebhook?.status"
              :status="store.currentWebhook.status"
            />
          </div>
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="runTest">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Event name</label>
            <input
              v-model="form.event_name"
              type="text"
              placeholder="webhook.test"
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
              :disabled="isIncoming"
            />
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">JSON payload</label>
            <textarea
              v-model="payloadText"
              rows="12"
              spellcheck="false"
              placeholder='{ "message": "AMS webhook test" }'
              class="min-h-[16rem] w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-3 font-mono text-xs text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
              :disabled="isIncoming"
            />
          </div>

          <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            <button
              type="submit"
              class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving || isIncoming || !store.currentWebhook"
            >
              <PaperAirplaneIcon class="h-4 w-4" />
              {{ store.saving ? 'Sending...' : 'Send test webhook' }}
            </button>
            <button
              type="button"
              class="rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50 disabled:opacity-60"
              :disabled="store.saving || isIncoming"
              @click="resetForm"
            >
              Reset
            </button>
          </div>

          <div
            v-if="isIncoming"
            class="rounded-[12px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
          >
            <p class="font-medium">Incoming webhook</p>
            <p class="mt-1 text-xs leading-relaxed text-amber-700">
              Test by POSTing to the incoming URL with a valid signature.
            </p>
            <div class="mt-3 flex items-start gap-2">
              <code
                class="block flex-1 break-all rounded-lg bg-white/80 px-3 py-2 font-mono text-xs text-slate-800 ring-1 ring-amber-100"
              >
                {{ store.currentWebhook?.incoming_url || '—' }}
              </code>
              <button
                v-if="store.currentWebhook?.incoming_url"
                type="button"
                class="shrink-0 rounded-[10px] border border-amber-200 bg-white px-2.5 py-1.5 text-xs font-medium text-amber-800 transition hover:bg-amber-100"
                @click="copyIncomingUrl"
              >
                Copy
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="overflow-hidden rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Result</h2>
            <p class="mt-1 text-sm text-slate-500">Latest delivery response from the test run.</p>
          </div>
          <StatusBadge
            v-if="store.lastTestLog?.status"
            :status="store.lastTestLog.status"
            kind="delivery"
          />
        </div>

        <EmptyState
          v-if="!store.lastTestLog"
          title="No test result yet"
          description="Send a test webhook to see status, timing, and response body here."
          class="mt-4 rounded-[12px] ring-1 ring-zinc-100"
        />

        <div v-else class="mt-5 space-y-4">
          <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
              <p class="text-xs font-medium text-zinc-500">HTTP</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">
                {{ store.lastTestLog.response_status || '—' }}
              </p>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
              <p class="text-xs font-medium text-zinc-500">Duration</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">
                {{ store.lastTestLog.duration_ms ?? '—' }} ms
              </p>
            </div>
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
              <p class="text-xs font-medium text-zinc-500">Attempts</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">
                {{ store.lastTestLog.attempts }} / {{ store.lastTestLog.max_attempts }}
              </p>
            </div>
          </div>

          <div
            v-if="store.lastTestLog.error_message"
            class="rounded-[12px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
          >
            {{ store.lastTestLog.error_message }}
          </div>

          <div>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">
              Response body
            </p>
            <pre
              class="max-h-80 overflow-auto rounded-[12px] bg-slate-900 p-4 text-xs text-slate-100"
            >{{ store.lastTestLog.response_body || '—' }}</pre>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  EyeIcon,
  PaperAirplaneIcon,
  PencilSquareIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import DirectionBadge from '@/modules/webhooks/components/DirectionBadge.vue';
import StatusBadge from '@/modules/webhooks/components/StatusBadge.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const route = useRoute();
const store = useWebhooksStore();
const toast = useToast();

const defaultPayload = '{\n  "message": "AMS webhook test"\n}';
const payloadText = ref(defaultPayload);
const form = reactive({ event_name: 'webhook.test' });

const isIncoming = computed(() => store.currentWebhook?.direction === 'incoming');

onMounted(async () => {
  store.lastTestLog = null;
  try {
    await store.fetchWebhook(route.params.id);
  } catch {
    // store.error already set
  }
});

function resetForm() {
  form.event_name = 'webhook.test';
  payloadText.value = defaultPayload;
}

async function copyIncomingUrl() {
  try {
    await navigator.clipboard.writeText(store.currentWebhook?.incoming_url || '');
    toast.success('Incoming URL copied.', 'Copied');
  } catch {
    toast.error('Unable to copy URL.');
  }
}

async function runTest() {
  let payload = {};
  try {
    payload = payloadText.value.trim() ? JSON.parse(payloadText.value) : {};
  } catch {
    store.error = 'Payload must be valid JSON.';
    toast.error('Payload must be valid JSON.', 'Validation Failed');
    return;
  }

  try {
    await store.testWebhook(route.params.id, {
      event_name: form.event_name,
      payload,
    });
    toast.success(store.successMessage || 'Test webhook sent.', 'Test sent');
  } catch (err) {
    toast.error(err?.message || store.error || 'Webhook test failed.', 'Test failed');
  }
}
</script>
