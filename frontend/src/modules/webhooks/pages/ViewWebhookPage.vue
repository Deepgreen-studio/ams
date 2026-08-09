<template>
  <div>
    <!-- <PageHeader
      :title="webhook?.name || 'Webhook details'"
      description="Webhook status, endpoint, and recent activity."
    >
      <template #actions>
        <template v-if="webhook">
          <RouterLink
            :to="{ name: 'webhooks.tester', params: { id: webhook.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >Test</RouterLink
          >
          <RouterLink
            :to="{ name: 'webhooks.edit', params: { id: webhook.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >Edit</RouterLink
          >
          <button
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="remove"
          >
            Delete
          </button>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <template v-if="webhook">
          <RouterLink
            :to="{ name: 'webhooks.tester', params: { id: webhook.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >Test</RouterLink
          >
          <RouterLink
            :to="{ name: 'webhooks.edit', params: { id: webhook.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >Edit</RouterLink
          >
          <button
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="remove"
          >
            Delete
          </button>
    </Teleport>
    <WebhookSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div v-if="store.loading && !webhook" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <div v-else-if="webhook" class="rounded-xl border border-slate-200 bg-white p-6">
      <dl class="grid gap-4 sm:grid-cols-2">
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Direction</dt>
          <dd class="mt-1 capitalize text-sm text-slate-900">{{ webhook.direction }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
          <dd class="mt-1 capitalize text-sm text-slate-900">{{ webhook.status }}</dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-xs uppercase tracking-wide text-slate-500">URL</dt>
          <dd class="mt-1 break-all text-sm text-slate-900">
            {{ webhook.direction === 'incoming' ? webhook.incoming_url : webhook.url || '—' }}
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Signature</dt>
          <dd class="mt-1 text-sm text-slate-900">
            {{ webhook.signature_algorithm }} · {{ webhook.signature_header }}
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Secret</dt>
          <dd class="mt-1 text-sm text-slate-900">
            {{ webhook.has_secret ? 'Configured (encrypted)' : 'Missing' }}
          </dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-xs uppercase tracking-wide text-slate-500">Events</dt>
          <dd class="mt-1 text-sm text-slate-900">
            {{ (webhook.subscribed_events || []).join(',') || '—' }}
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Last success</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ webhook.last_success_at || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Last failure</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ webhook.last_failure_at || '—' }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const route = useRoute();
const router = useRouter();
const store = useWebhooksStore();
const webhook = computed(() => store.currentWebhook);

onMounted(() => store.fetchWebhook(route.params.id));

async function remove() {
  await store.deleteWebhook(route.params.id);
  await router.push({ name: 'webhooks.index' });
}
</script>
