<template>
  <div>
    <MonitoringSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />

    <div v-else-if="data" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in cards"
        :key="card.label"
        class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const data = computed(() => store.webhookMonitor);

const cards = computed(() => [
  { label: 'Success rate', value: `${data.value?.success_rate ?? 0}%` },
  { label: 'Total', value: data.value?.summary?.total ?? 0 },
  { label: 'Success', value: data.value?.summary?.success ?? 0 },
  { label: 'Failed', value: data.value?.summary?.failed ?? 0 },
]);

onMounted(() => store.fetchWebhookMonitor());
</script>
