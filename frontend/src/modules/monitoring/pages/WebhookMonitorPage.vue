<template>
  <div>
    <PageHeader title="Webhook Monitor" description="Webhook delivery success rate and volume." />
    <MonitoringSubnav />
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <div v-else-if="data" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase text-slate-500">Success rate</p>
        <p class="mt-2 text-2xl font-semibold">{{ data.success_rate }}%</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase text-slate-500">Total</p>
        <p class="mt-2 text-2xl font-semibold">{{ data.summary?.total ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase text-slate-500">Success</p>
        <p class="mt-2 text-2xl font-semibold">{{ data.summary?.success ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase text-slate-500">Failed</p>
        <p class="mt-2 text-2xl font-semibold">{{ data.summary?.failed ?? 0 }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const data = computed(() => store.webhookMonitor);
onMounted(() => store.fetchWebhookMonitor());
</script>
