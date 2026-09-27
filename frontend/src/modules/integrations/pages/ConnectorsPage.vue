<template>
  <div>
    <IntegrationsHubSubnav />

    <div
      v-if="error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div v-if="loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="index in 6" :key="index" class="h-40 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="connector in connectors"
        :key="connector.key"
        class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100"
      >
        <div class="flex items-start justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">{{ connector.label }}</h2>
          <span
            v-if="connector.handles_webhooks"
            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
          >
            Webhook handler
          </span>
        </div>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ connector.description }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
          <span
            v-for="capability in connector.capabilities"
            :key="capability"
            class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700"
          >
            {{ capabilityLabel(capability) }}
          </span>
        </div>
      </article>
    </div>

    <p v-if="!loading && connectors.length === 0" class="text-sm text-slate-500">
      No connectors are registered.
    </p>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import IntegrationsHubSubnav from '@/modules/integrations/components/IntegrationsHubSubnav.vue';
import { integrationService } from '@/modules/integrations/services/integrationService';

const connectors = ref([]);
const loading = ref(true);
const error = ref('');

const labels = {
  api: 'API',
  oauth: 'OAuth',
  webhook: 'Webhooks',
  api_key: 'API key',
  scheduled_sync: 'Scheduled sync',
  queue_retry: 'Queues and retries',
  monitoring: 'Monitoring',
  credentials: 'Credentials',
};

function capabilityLabel(capability) {
  return labels[capability] || capability;
}

onMounted(async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await integrationService.connectors();
    connectors.value = data.data?.connectors ?? [];
  } catch (err) {
    error.value = err?.message || 'Unable to load the connector catalog.';
  } finally {
    loading.value = false;
  }
});
</script>
