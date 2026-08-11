<template>
  <div>
    <MonitoringSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-48 animate-pulse rounded-[12px] bg-zinc-100" />

    <template v-else-if="data">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in metricCards"
          :key="card.label"
          class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="(value, key) in data.statuses || {}"
          :key="key"
          class="rounded-[12px] bg-white px-5 py-4 ring-1 ring-zinc-100"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
            {{ formatStatusKey(key) }}
          </p>
          <p class="mt-2 text-sm font-semibold capitalize" :class="statusClass(value)">
            {{ value }}
          </p>
        </div>
      </div>

      <SimpleLineChart
        title="API response history"
        :points="data.history || []"
        value-key="avg_response_ms"
      />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import SimpleLineChart from '@/modules/monitoring/components/SimpleLineChart.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const data = computed(() => store.apiMonitor);

const metricCards = computed(() => [
  { label: 'Avg response', value: `${data.value?.avg_response_ms ?? 0} ms` },
  { label: 'Error rate', value: `${data.value?.summary?.error_rate ?? 0}%` },
  { label: 'Auth success', value: `${data.value?.authentication?.success_rate ?? 0}%` },
  { label: 'Rate-limit hits', value: data.value?.rate_limits?.hits ?? 0 },
]);

function formatStatusKey(key) {
  return String(key).replaceAll('_', ' ');
}

function statusClass(value) {
  if (value === 'healthy') return 'text-emerald-700';
  if (value === 'degraded') return 'text-amber-700';
  if (value === 'unhealthy') return 'text-rose-700';
  return 'text-slate-900';
}

onMounted(() => store.fetchApiMonitor());
</script>
