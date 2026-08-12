<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div v-if="webhook" class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{ name: 'webhooks.tester', params: { id: webhook.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          <BeakerIcon class="h-4 w-4 text-slate-500" />
          Test
        </RouterLink>
        <RouterLink
          :to="{ name: 'webhooks.edit', params: { id: webhook.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700"
          @click="showDelete = true"
        >
          <TrashIcon class="h-4 w-4 text-white" />
          Delete
        </button>
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
      v-if="store.loading && !webhook"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="webhook" class="grid gap-6 xl:grid-cols-12">
      <aside class="xl:col-span-4">
        <div class="rounded-[12px] bg-white p-6 sm:p-7 ring-1 ring-zinc-100">
          <div class="flex flex-col items-start gap-4">
            <div
              class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-lg font-semibold text-brand-700"
            >
              {{ initials }}
            </div>
            <div class="min-w-0 w-full">
              <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
                {{ webhook.name }}
              </h2>
              <p class="mt-1 truncate text-sm text-slate-500">
                {{ webhook.slug }} · {{ webhook.company?.company_name || '—' }}
              </p>
              <div class="mt-3 flex flex-wrap gap-1.5">
                <DirectionBadge :direction="webhook.direction" />
                <StatusBadge :status="webhook.status" />
              </div>
            </div>
          </div>

          <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5">
            <div class="flex items-start justify-between gap-3">
              <dt class="text-sm text-zinc-500">Timeout</dt>
              <dd class="text-right text-sm font-medium text-slate-900">
                {{ webhook.timeout ?? '—' }}s
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="text-sm text-zinc-500">Retry attempts</dt>
              <dd class="text-right text-sm font-medium text-slate-900">
                {{ webhook.retry_attempts ?? '—' }}
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="text-sm text-zinc-500">Secret</dt>
              <dd class="text-right text-sm font-medium text-slate-900">
                {{ webhook.has_secret ? 'Configured' : 'Missing' }}
              </dd>
            </div>
          </dl>
        </div>
      </aside>

      <section class="space-y-6 xl:col-span-8">
        <div class="grid gap-3 sm:grid-cols-2">
          <div class="rounded-[12px] bg-white px-4 py-4 ring-1 ring-zinc-100">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Last success</p>
            <p class="mt-1.5 text-sm font-semibold text-slate-900">
              {{ formatDate(webhook.last_success_at) }}
            </p>
          </div>
          <div class="rounded-[12px] bg-white px-4 py-4 ring-1 ring-zinc-100">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Last failure</p>
            <p class="mt-1.5 text-sm font-semibold text-slate-900">
              {{ formatDate(webhook.last_failure_at) }}
            </p>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100">
          <h3 class="text-base font-semibold text-slate-900">Endpoint</h3>
          <p class="mt-1 text-sm text-slate-500">
            Delivery URL, signature settings, and subscribed events.
          </p>

          <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5 sm:col-span-2">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-xs font-medium text-zinc-500">URL</p>
                  <p class="mt-1 break-all text-sm font-semibold text-slate-900">
                    {{ endpointUrl }}
                  </p>
                </div>
                <button
                  v-if="endpointUrl !== '—'"
                  type="button"
                  class="shrink-0 rounded-[10px] border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-zinc-50"
                  @click="copyUrl"
                >
                  Copy
                </button>
              </div>
            </div>

            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
              <p class="text-xs font-medium text-zinc-500">Signature algorithm</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">
                {{ formatAlgorithm(webhook.signature_algorithm) }}
              </p>
            </div>

            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
              <p class="text-xs font-medium text-zinc-500">Signature header</p>
              <p class="mt-1 break-all text-sm font-semibold text-slate-900">
                {{ webhook.signature_header || '—' }}
              </p>
            </div>

            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5 sm:col-span-2">
              <p class="text-xs font-medium text-zinc-500">Subscribed events</p>
              <div v-if="events.length" class="mt-2 flex flex-wrap gap-1.5">
                <span
                  v-for="event in events"
                  :key="event"
                  class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-2.5 py-1 font-mono text-xs text-slate-700"
                >
                  {{ event }}
                </span>
              </div>
              <p v-else class="mt-1 text-sm font-semibold text-slate-900">—</p>
            </div>

            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5 sm:col-span-2">
              <p class="text-xs font-medium text-zinc-500">Description</p>
              <p class="mt-1 whitespace-pre-wrap text-sm font-medium text-slate-900">
                {{ webhook.description || '—' }}
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete webhook"
      :message="`Delete ${webhook?.name || 'this webhook'}? This action cannot be undone.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import {
  BeakerIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import DirectionBadge from '@/modules/webhooks/components/DirectionBadge.vue';
import StatusBadge from '@/modules/webhooks/components/StatusBadge.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const route = useRoute();
const router = useRouter();
const store = useWebhooksStore();
const toast = useToast();
const showDelete = ref(false);

const webhook = computed(() => store.currentWebhook);

const initials = computed(() =>
  String(webhook.value?.name || 'W')
    .trim()
    .slice(0, 2)
    .toUpperCase(),
);

const endpointUrl = computed(() => {
  if (!webhook.value) return '—';
  return webhook.value.direction === 'incoming'
    ? webhook.value.incoming_url || '—'
    : webhook.value.url || '—';
});

const events = computed(() => webhook.value?.subscribed_events || []);

onMounted(async () => {
  try {
    await store.fetchWebhook(route.params.id);
  } catch {
    // store.error already set
  }
});

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}

function formatAlgorithm(value) {
  if (!value) return '—';
  return String(value).replaceAll('_', ' ').toUpperCase();
}

async function copyUrl() {
  try {
    await navigator.clipboard.writeText(endpointUrl.value);
    toast.success('Endpoint URL copied.', 'Copied');
  } catch {
    toast.error('Unable to copy URL.');
  }
}

async function confirmDelete() {
  if (!webhook.value) return;

  const name = webhook.value.name || 'Webhook';

  try {
    const data = await store.deleteWebhook(route.params.id);
    showDelete.value = false;
    toast.success(data?.message || `${name} deleted successfully.`, 'Webhook deleted');
    await router.push({ name: 'webhooks.index' });
  } catch (err) {
    toast.error(err?.message || store.error || 'Unable to delete webhook.', 'Delete failed');
  }
}
</script>
